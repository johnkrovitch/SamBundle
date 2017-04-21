# SamBundle
SamBundle for Symfony. Add a command to build the assets using configuration in app/config.
SamBundle use the Sam library (stand for Simple Asset Manager) and will allow to configure how your assets will be build,
minified and merged.


## Introduction
SamBundle will read a yaml configuration file. This file should contains a list of tasks. Each task is a list of sources
files (js, css, scss...), a list of filters applied to those files, and a list a destination file.

## Installation
```bash
    composer require johnkrovitch/sam-bundle
```

## Configuration

Add your configuration in config.yml

```yml    
    jk_assets:
        # filters configuration
        filters:
            compass:
                # path to compass binary (if it is in $PATH, you could leave the default)
                bin: compass
            # activate merge, minify and copy filters
            merge: ~
            minify: ~
            copy: ~
        tasks:
            # a task
            # main.css is just a name, you could put what ever you want, but each task should have a unique name
            main.css:
                # first will apply the Compass filter, then the merge filter, then we minify
                # the process should be as following :
                #   1) the Compass filter will only take the ".scss" files, and compile them to ".css" files. The ".scss"
                # files we be replaced by the ".css" files in the sources list
                #   2) the merge filter will take all the files in the sources list
                filters:
                    - compass
                    - merge
                    - minify
                sources:
                    - app/Resources/assets/sass/main.scss
                    - vendor/components/bootstrap/css/bootstrap.min.css
                    - vendor/components/bootstrap/css/bootstrap-theme.min.css
                    - vendor/components/font-awesome/css/font-awesome.min.css
                    - app/Resources/assets/css/hover-min.css
                destinations:
                    - web/css/main.css
    
            fonts:
                filters: ~
                sources:
                    - vendor/components/bootstrap/fonts/
                    - vendor/components/font-awesome/fonts/
                destinations:
                    - web/fonts/
    
            cms.js:
                filters:
                    - merge
                    - minify
                sources:
                    - src/JK/CmsBundle/Resources/assets/js/jquery.iframe-transport.js
                    - src/JK/CmsBundle/Resources/assets/js/cms/fileupload.js
                    - vendor/blueimp/jquery-file-upload/js/jquery.fileupload.js
                destinations:
                    - src/JK/CmsBundle/Resources/public/js/cms.js
    
            tinymce.css:
                filters:
                    - compass
                    - merge
                    - minify
                sources:
                    - src/JK/CmsBundle/Resources/assets/sass/tinymce.scss
                    - web/css/custom-tinymce.css
                destinations:
                    - src/JK/CmsBundle/Resources/public/css/tinymce.css
```

## Usage

After configuring your assets, you can execute the tasks with the following command :
```bash
    bin/console jk:assets:build
```

During development, you can use the watch command which monitor the configured assets and rebuild automatically the
assets.
```bash
    bin/console jk:assets:watch
```
