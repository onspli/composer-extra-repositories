# Composer Inject Repositories Plugin

This Composer plugin allows loading repository definitions from external files and injecting them to composer.json. Files with additional repositories can be stored locally or fetched from remote locations (http, git).

## Usage

You have several composer packages stored in private Github repositories (and thus not available on public packagist.org repository), and you want to use the packages in several composer projects. 

Install plugin globally.
```sh
composer global require onspli/composer-inject-repositories
```

Create file `repos.json` listing all the repositories you want to use (as described on https://getcomposer.org/doc/05-repositories.md)
```json
{
    "repositories": [
        {
            "type": "git",
            "url": "git@github.com:onspli/private-package-1.git"
        },
        {
            "type": "git",
            "url": "git@github.com:onspli/private-package-2.git"
        },
        {
            "type": "git",
            "url": "git@github.com:onspli/private-package-3.git"
        }
    ]
}
```

Add extra option `inject-repositories` to `composer.json` of your project.
```json
{
    "name": "onspli/project-using-private-repos",
    "type": "project",
    "extra": {
        "inject-repositories": [
            {
                "type": "local",
                "path": "/path/to/repos.json"
            },
            {
                "type": "remote",
                "url": "https://my-domain.cz/repos.json"
            },
            {
                "type": "git",
                "url": "git@github.com:onspli/private-repos.git",
                "file": "repos.json"
            }
        ]
    },
    "require": {
        "onspli/private-package-2": "dev-main"
    }
}
```

The plugin reads `repos.json` file and injects repositories to `composer.json` of the project, so during composer invocations it effectively looks like:
```json
{
    "name": "onspli/project-using-private-repost",
    "type": "project",
    "require": {
        "onspli/private-package-2": "dev-main"
    },
    "repositories": [
        {
            "type": "git",
            "url": "git@github.com:onspli/private-package-1.git"
        },
        {
            "type": "git",
            "url": "git@github.com:onspli/private-package-2.git"
        },
        {
            "type": "git",
            "url": "git@github.com:onspli/private-package-3.git"
        }
    ]
}
```

## Verbose mode

See what files and repositories are injected using composer verbose mode:
```sh
composer install --verbose
```