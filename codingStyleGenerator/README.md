# Coding Style Generator
Generate a coding style based on your PHP Insights configuration file.  

```bash
composer require worksome/phpinsights-coding-style-generator
```

## Usage
in the composer bin folder a file named `codingStyleGenerator` will be available.  
The first argument of the tool is the output directory.

This file takes the following parameters:
- `--config-path`: Path to the phpinsights configuration file.
- `--insight-config-path`: Path to coding style configuration file.

An example command running the tool:
```bash
codingStyleGenerator docs --config-path=config/insights.php --insight-config-path=config/codingStyle.php
```

The tool will generate a folder with a VuePress installation.  
In here there will be some files which are overridden each time the coding style is generated.  

To render the vuepress installation simply do the following:
````bash
# install
yarn global add vuepress
# OR npm install -g vuepress

# start writing
vuepress dev

# build to static files
vuepress build
````


## Configuration
The configuration file is split up in groups, sub-groups and insights.  
An example group would be `Generic PHP` or `Laravel`, a sub-group would be `Functions` or `Code`.  
Insights are the insights which are used by php insights.  


```php
return [
    'groups' => [
        Group::GENERIC_PHP => [
            'groups' => [
                SubGroup::CODE => [
                    'insights' => [
                        \ObjectCalisthenics\Sniffs\ControlStructures\NoElseSniff::class => [
                            Property::TITLE => 'Avoid else',
                            Property::DESCRIPTION => <<<DESC
                            Else statements adds confusion and often does not contribute to more readable code.  
                            It is recommended to avoid else statements. Usage of early returns can often replace else statements,
                            which also in return will result with the happy path being last.
                            DESC,
                            Property::BAD_CODE => /** @lang PHP */ <<<BAD_CODE
                            if(\$state === 'approved') {
                                return "Happy life";
                            }
                            else {
                                return "bad state";
                            }
                            BAD_CODE,
                            Property::GOOD_CODE => /** @lang PHP */ <<<GOOD_CODE
                            if (\$state !== 'approved') {
                                return "bad state";
                            }
                            
                            return "Happy life";
                            GOOD_CODE,
                        ],
                    ],
                ],
            ],
        ],
        Group::LARAVEL => [
            'groups' => [
                'Controllers' => [
                    'insights' => [
                        'Prefer array parameters when using view' => [
                            Property::ALWAYS_SHOW => true,
                            Property::DESCRIPTION => <<<DESC
                            When passing data to a blade file, it is done with the `view` method.  
                            In the case of passing data, always use the array syntax.
                            DESC,
                            Property::BAD_CODE => /** @lang PHP */ <<<BAD_CODE
                            \$value = 'My value';
                            
                            return view('view', compact('value'));
                            BAD_CODE,
                            Property::GOOD_CODE => /** @lang PHP */ <<<GOOD_CODE
                            return view('view', ['value' => 'My value']);
                            GOOD_CODE,
                        ],
                    ],
                ],
            ],
        ],
    ],
];

```