(function () {
    var app = angular.module('app');
    app.factory('authInterceptor', function ($rootScope, $q, $window, $localStorage) {
        return {
            request: function (config) {

                var currentUser = $rootScope.globals.currentUser;
                
                config.headers = config.headers || {};
                if (currentUser.authdata) {
                    //alert($localStorage.apiKey);
                    config.headers.Authorization = currentUser.authdata;
                }
                return config;
            },
            response: function (response) {
                if (response.status === 401) {
                    // handle the case where the user is not authenticated
                }
                return response || $q.when(response);
            }
        };
    });
})();