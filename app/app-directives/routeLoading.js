(function () {

    var injectParams = ['$rootScope'];

    var routeLoading = function ($rootScope) {

        var link = function (scope, element, attrs) {
            scope.isRouteLoading = false;

            $rootScope.$on('$routeChangeStart', function () {
                scope.isRouteLoading = true;
            });
            $rootScope.$on('$routeChangeSuccess', function () {
                scope.isRouteLoading = false;
            });
        };

        return {
            restrict: 'E',
            template: "<div ng-show='isRouteLoading'>Loading</div>",
            replace: true,
            link: link
        };
    };

    routeLoading.$inject = injectParams;

    angular.module('app').directive('routeLoading', routeLoading);

}());