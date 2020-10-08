import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';

import { AppComponent } from './app.component';
import {HttpClient, HttpClientModule} from '@angular/common/http';
import { ReactiveFormsModule } from '@angular/forms';
import {FORMLY_CONFIG, FormlyModule} from '@ngx-formly/core';
import { FormlyBootstrapModule } from '@ngx-formly/bootstrap';

import { UserService } from './user.service';

import { TranslateModule, TranslateLoader } from '@ngx-translate/core';
import { TranslateHttpLoader } from '@ngx-translate/http-loader';
import { TranslateService } from '@ngx-translate/core';
import { registerTranslateExtension } from './translate.extension';
import { PanelWrapperComponent } from './panel-wrapper.component';
import {NgbdTooltipBasicModule} from './tooltip-basic.module';
import {NgbTooltipModule} from '@ng-bootstrap/ng-bootstrap';
import { FontAwesomeModule } from '@fortawesome/angular-fontawesome';


// https://github.com/ngx-translate/http-loader/issues/25#issuecomment-413417035
// cached json files
// AoT requires an exported function for factories
// cache busting
export function HttpLoaderFactory(httpClient: HttpClient) {
  return new TranslateHttpLoader(httpClient, 'assets/i18n/', '.json?cb=' + new Date().getTime());
}

@NgModule({
  declarations: [
    AppComponent,
    PanelWrapperComponent
  ],
  imports: [
    BrowserModule,
    ReactiveFormsModule,
    HttpClientModule,
    FormlyBootstrapModule,
    FormlyModule.forRoot({
      wrappers: [
        {name: 'custompanel', component: PanelWrapperComponent},
      ],
    }),
    HttpClientModule,
    TranslateModule.forRoot({
      loader: {
        provide: TranslateLoader,
        useFactory: HttpLoaderFactory,
        deps: [HttpClient],
      },
    }),
    NgbdTooltipBasicModule,
    NgbTooltipModule,
    FontAwesomeModule,
  ],
  // providers: [UserService],
  providers: [
      UserService,
    { provide: FORMLY_CONFIG, multi: true, useFactory: registerTranslateExtension, deps: [TranslateService] },
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
