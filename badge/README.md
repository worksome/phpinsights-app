# Badge system
Generate badges based on results from PHP Insights.

## API
This package exposes an API for generating badges, which can be used together with GitHub Actions.

### Creating / Updating badges
When creating/updating the data, a JSON result has to be supplied to the following url
```
https://badges.phpinsights.app/{username}/{repository}/{branch}

// Example using own repository
https://badges.phpinsights.app/worksome/phpinsights-app/master
```
Where the payload has to follow the [`schema.json`](https://github.com/worksome/phpinsights-app/blob/master/badge/schema.json) file.
```json
{
  "summary": {
    "code": 80,
    "architecture": 60.6,
    "complexity": 90.3,
    "style": 60
  },
  "requirements": {
    "min-code": 60,
    "min-architecture": 60,
    "min-style": 60,
    "min-complexity": 60
  }
}
```
Requirements are optional, and the values defaults to 80.

## Fetching badges
The badge API exposed endpoints for getting badges based on the different matrices.  
It returns a JSON which is compatible with [shields.io endpoint](https://shields.io/endpoint) system, so a shield.io
badge can be generated. The endpoints are the following:
```
https://badges.phpinsights.app/{username}/{repository}/{branch}/code
https://badges.phpinsights.app/{username}/{repository}/{branch}/complexity
https://badges.phpinsights.app/{username}/{repository}/{branch}/architecture
https://badges.phpinsights.app/{username}/{repository}/{branch}/style
```
Which can be passed into shield.io's endpoint like so:
```
https://img.shields.io/endpoint?url=https://badges.phpinsights.app/worksome/phpinsights-app/master/code
```
Which returns a badge:  
![badge](https://img.shields.io/endpoint?url=https://badges.phpinsights.app/worksome/phpinsights-app/master/code)

The badge can be changed to your liking, for example the style can be changed by appending `&tyle={type}`, where the
following types are available:  
| type          | badge |
|---------------|-----------|
| plastic       | ![plastic](https://img.shields.io/endpoint?url=https://badges.phpinsights.app/worksome/phpinsights-app/master/code&style=plastic)|
| flat          | ![flat](https://img.shields.io/endpoint?url=https://badges.phpinsights.app/worksome/phpinsights-app/master/code&style=flat)|
| flat-square   | ![flat-square](https://img.shields.io/endpoint?url=https://badges.phpinsights.app/worksome/phpinsights-app/master/code&style=flat-square)|
| for-the-badge | ![for-the-badge](https://img.shields.io/endpoint?url=https://badges.phpinsights.app/worksome/phpinsights-app/master/code&style=for-the-badge)|
| social        | ![social](https://img.shields.io/endpoint?url=https://badges.phpinsights.app/worksome/phpinsights-app/master/code&style=social)|

To see all options which can be changed, checkout [shield.io's website](https://shields.io/endpoint).

## Local development
Install `wrangler` and run `npm install`.  
Afterwards you should be able to run:
```bash
wrangler preview
```
A browser window will open where the worker can be tested out.  
It can also be ran with a file-watcher, so changes are reflected:
```bash
wrangler preview --watch
```

### Deployment
To deploy to production run `wrangler publish --env production`.  
For deploying to dev domain, use `wrangler publish`.


