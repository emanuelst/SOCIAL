import { Injectable } from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {Observable, forkJoin} from 'rxjs';
import {FormlyFieldConfig} from '@ngx-formly/core';
import {catchError} from 'rxjs/operators';

@Injectable()
export class UserService {
  constructor(private http: HttpClient) {}

  getUserData(): Observable<any> {
    return forkJoin([this.getUser(), this.getFields()]);
  }

  getUser() {
    return this.http.get<{ firstName: string, lastName: string }>('assets/json-powered/user.json?cb=' + new Date().getTime());
  }

  getFields() {
    // get pathname
    const pathString = location.pathname
    let pathStringJSON: string

    // on '/' we default to user-form.json anyways, to prevent error; else we build the url
    if (pathString === '/') {
      pathStringJSON = 'user-form.json'
    } else {
      // https://stackoverflow.com/questions/3840600/javascript-regular-expression-remove-first-and-last-slash
      const pathStringWithoutSlash = pathString.replace(/^\/|\/$/g, '');
      pathStringJSON = pathStringWithoutSlash + '.json'
    }

    // cache busting
    // https://stackoverflow.com/questions/63182874/angular-8-http-get-request-with-no-cache
    // https://stackoverflow.com/questions/46019771/catching-errors-in-angular-httpclient
    // build default http and http request
    const defaulthttp$ = this.http.get<FormlyFieldConfig[]>('assets/json-powered/' + 'user-form.json' + '?cb=' + new Date().getTime())
    const http$ = this.http.get<FormlyFieldConfig[]>('assets/json-powered/' + pathStringJSON + '?cb=' + new Date().getTime())

    // try http request and handle error
    // https://blog.angular-university.io/rxjs-error-handling/
    // https://stackoverflow.com/questions/50920833/how-can-we-get-httpclient-status-code-in-angular-4/50929021
    // https://stackoverflow.com/a/55974242/841052
    const value = http$
        .pipe(
            catchError(err => {
              // "HttpErrorResponse" if we get the wrong json (when there's no json, we return the default html (routing))
              // this leads to "Http failure during parsing for ***"
              // so, on error we try again with the default url; if this throws an error we're out of luck
              console.log(err);
              return defaulthttp$;
            })
        );

    return value;
  }
}
