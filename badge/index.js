import Url from "url-parse";

addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request))
});


/**
 * Respond with hello worker text
 * @param {Request} request
 */
async function handleRequest(request) {
  const url = new Url(request.url);
  const [, username, repository, branch = "master", ...params] = url.pathname.split('/');
  console.log(url);

  if (['PUT', 'POST', 'PATCH'].includes(request.method)) {
    return updateBadges(request, {username, repository, branch});
  }

  return getBadge(request, {username, repository, branch}, params[0])
}

/**
 * Sets the badge in the cache and response with success.
 * @param {Request} request
 * @param {string} username
 * @param {string} repository
 * @param {string} branch
 */
async function updateBadges(request, {username, repository, branch}) {
  // Uses let as defaults are generated when validating.
  let data;
  try {
    data = await request.json();
  } catch (e) {
    return new Response(
      JSON.stringify({
        error: true,
        message: "Cannot parse input as JSON.",
        exception: e.message
      }), {
        status: 422
      }
    )
  }

  const canUpdate = await canUpdateBadges(username, repository, request.headers.get('Authorization'));
  if (canUpdate !== true) {
    return canUpdate;
  }

  await badges.put(`${username}/${repository}/${branch}`, JSON.stringify(data));

  return jsonResponse({
    status: 201,
    statusText: `Badges for ${username}/${repository} has been updated!`
  })
}

/**
 * Responds with the badge
 * @param {Request} request
 * @param {string} username
 * @param {string} repository
 * @param {string} branch
 * @param {string} type
 */
async function getBadge(request, {username, repository, branch}, type) {
  // Handle invalid types.
  if (!['code', 'complexity', 'architecture', 'style'].includes(type)) {
    return new Response(
      JSON.stringify({
        schemaVersion: 1,
        label: `PHPInsights | Unknown`,
        message: `??`
      }), {
        status: 404,
      }
    )
  }

  let data = await badges.get(`${username}/${repository}/${branch}`, 'json');

  const minRequirement = data && (data.hasOwnProperty('requirements') && data.requirements.hasOwnProperty(`min-${type}`))
    ? data.requirements[`min-${type}`] : null;
  const percentage = data ? data['summary'][type] : null;

  // Handle if no percentage is saved.
  if (!percentage) {
    return new Response(
      JSON.stringify({
        schemaVersion: 1,
        label: `PHPInsights | ${type[0].toUpperCase() + type.slice(1)}`,
        message: `??`
      }), {
        status: 404,
      }
    )
  }

  return new Response(
    JSON.stringify({
      schemaVersion: 1,
      label: `PHPInsights | ${type[0].toUpperCase() + type.slice(1)}`,
      message: `${percentage}%`,
      isError: minRequirement === null ? false : minRequirement < percentage,
      color: getColor(percentage)
    }), {
      status: 200,
    }
  )
}

async function gitHubRequest({query, variables = {}, authorization}) {
  return fetch(
    "https://api.github.com/graphql",
    {
      body: JSON.stringify({
        query,
        variables
      }),
      method: "POST",
      headers: {
        "Authorization": authorization,
        'content-type': 'application/json',
        'User-Agent': 'PHP Insights App Action'
      }
    }
  );
}

/**
 * @param {number} status
 * @param json
 * @param params
 */
function jsonResponse({json = {}, status = 200, ...params}) {
  return new Response(
    JSON.stringify(json),
    {
      status,
      headers: {
        'content-type': 'application/json',
      },
      ...params
    }
  )
}

function getColor(percentage) {
  if (percentage >= 80) {
    return 'success';
  }
  if (percentage >= 50) {
    return 'yellow';
  }
  return 'red';
}

async function canUpdateBadges(repositoryUsername, repository, authorizationHeader) {
  const query = `query permission($repo: String!, $owner: String!) {
    viewer {
      login
      repositories(first: 1) {
        nodes {
          viewerRepositoryName: name
          owner {
            viewerRepositoryLogin: login
          }
        }
      }
    }
    repository(name: $repo, owner: $owner) {
      viewerPermission
    }
  }`;

  const response = await gitHubRequest({
    query,
    variables: {
      owner: repositoryUsername,
      repo: repository,
    },
    authorization: authorizationHeader
  })

  if (!response.ok) {
    return jsonResponse({
      json: {
        error: true,
        message: "Failed authenticating with GitHub.",
      },
      status: 400,
    })
  }

  const { data: { repository: { viewerPermission }, viewer: { login, repositories: { nodes: [ { viewerRepositoryName = undefined, owner: { viewerRepositoryLogin = undefined } = {} } ] = [] } } } } = await response.json();

  console.log(viewerPermission, login, viewerRepositoryName, viewerRepositoryLogin);

  // Handle permissions for GitHub action bot
  if (login === 'github-actions[bot]' && (viewerRepositoryName !== repository || viewerRepositoryLogin !== repositoryUsername)) {
    return jsonResponse({
      json: {
        error: true,
        message: "GitHub Actions do not have permission to update the badges on this repository."
      },
      status: 400,
    });
  }

  // Handle permissions for users.
  if (!['ADMIN', 'MAINTAIN', 'WRITE'].includes(viewerPermission) && login !== 'github-actions[bot]') {
    return jsonResponse({
      json: {
        error: true,
        message: "User does not have permission to update badges on the specified repository.",
      },
      status: 400
    });
  }

  return true;
}