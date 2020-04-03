# PHP Insights App
![PHP Insights - Code](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadges.phpinsights.app%2Fworksome%2Fphpinsights-app%2Fmaster%2Fcode)
![PHP Insights - Complexity](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadges.phpinsights.app%2Fworksome%2Fphpinsights-app%2Fmaster%2Fcomplexity)
![PHP Insights - Architecture](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadges.phpinsights.app%2Fworksome%2Fphpinsights-app%2Fmaster%2Farchitecture)
![PHP Insights - Style](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadges.phpinsights.app%2Fworksome%2Fphpinsights-app%2Fmaster%2Fstyle)

Running PHP Insights in the best way possible!  

This tool will run PHP Insights for you and provide you with GitHub reviews, badges and more!  
To use it add the example workflow file to your GitHub repository, and the GitHub Action will run automatically 🎩

![Review Example Comment](https://raw.githubusercontent.com/worksome/phpinsights-app/master/art/review-example-comment.png)
![Review Example](https://raw.githubusercontent.com/worksome/phpinsights-app/master/art/review-example.png)

Adding the following workflow file, will make phpinsights run on pull request, where it will create a review with the errors.  
We also allow it here to run on pushes to the master branch. By allowing this, the badges for the master branch will be updated.

```yaml
on:
  pull_request:
  push:
    branches:
      - master

jobs:
  static_analysis:
    runs-on: ubuntu-latest
    name: Static analysis
    steps:
      # You must check out your repository, so we can analyse it
      - name: Checkout
        uses: actions/checkout@v2
      - name: PHP Insights App
        uses: worksome/phpinsights-app@v0.1
        with:
          repo_token: "${{ secrets.GITHUB_TOKEN }}"
          workingDir: '.'
          memory_limit: '1024M'
```

The action has the following parameters:
- `repo_token`: The GitHub API token, which is used to generate the review. Keeping it as `${{ secrets.GITHUB_TOKEN }}` will make it use the token from the GitHub action.
- `workingDir`: This set's the directory which we will run the tool on. This is useful if you have a repository with multiple projects in it.
- `config_path`: (optional) Defines the path to where you php insights config file is, if it's not located in the default location.
- `memory_limit`: (optional) Set's the PHP limit, if more than the default memory is needed to run php insights on your code.