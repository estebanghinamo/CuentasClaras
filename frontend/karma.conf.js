// Config completa: pasar 'karmaConfig' en angular.json hace que @angular/build:karma
// NO cargue su config default (frameworks/plugins/reporters), asi que hay que
// reproducirla acá - el unico motivo de este archivo es agregar el reporter 'lcov'
// (consumido por SonarQube Cloud via sonar.javascript.lcov.reportPaths, ver
// ci.yml y sonar-project.properties), que el builder no expone por CLI ni por
// angular.json.
const path = require('node:path');

module.exports = function (config) {
  config.set({
    basePath: '',
    frameworks: ['jasmine'],
    plugins: ['karma-jasmine', 'karma-chrome-launcher', 'karma-jasmine-html-reporter', 'karma-coverage'].map((p) => require.resolve(p)),
    jasmineHtmlReporter: {
      suppressAll: true,
    },
    coverageReporter: {
      dir: path.join(__dirname, 'coverage', 'frontend'),
      subdir: '.',
      reporters: [{ type: 'html' }, { type: 'text-summary' }, { type: 'lcovonly', file: 'lcov.info' }],
    },
    reporters: ['progress', 'kjhtml'],
    browsers: ['Chrome'],
    restartOnFileChange: true,
  });
};
