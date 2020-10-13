# SOCIAL

SOCIAL is a Social Welfare Calculator Adaptable to Varying Legal Frameworks. 

SOCIAL enables the modeling and editing of social welfare calculators and questionnaires and can generate web applications based on these models. The model editor uses a Directed acyclic graph (DAG) to model calculators and questionnaires and is based on [DAGitty](https://github.com/jtextor/dagitty) by Textor et al., which was modified to enable domain-specfific modeling. SOCIAL can then convert the model to a web application.

## Testing

1. Download the Source Code from GitHub
2. Run `npm install`
3. Run `ng serve` for a dev server. To test the editor, set up a `PHP` server to serve the files from `dagitty-social/gui`. 

## Build

Run `ng build` to build the project. The build artifacts will be stored in the `dist/` directory. Use the `--prod` flag for a production build.

## Deployment

Assuming a Ubuntu 20.04.1 LTS server. 
To install the LAMP Stack, you can follow the instructions by [Linode](https://www.linode.com/docs/web-servers/lamp/how-to-install-a-lamp-stack-on-ubuntu-18-04/).

1. Upload the build artifacts (the application) to the server.
2. Upload the directory `dagitty-social/gui` (the editor) to the server.
3. Place the `Python` Script in `/usr/local/bin/dot_to_formly_json-python` 
4. Ensure the `editor/models` and `assets/json-powered` directories can be written to using the `Python` script (permissions `rwxrwxrwx`) 

## Online

A version has also been deployed at [https://social.cosy.univie.ac.at](https://social.cosy.univie.ac.at)

## Notes

This project was generated with [Angular CLI](https://github.com/angular/angular-cli) version 9.0.5.

