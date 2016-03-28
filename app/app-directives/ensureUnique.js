(function () {

    var injectParams = ['$http'];

    var ensureUnique = function ($http) {

        var link = function(scope, ele, attrs, c) {
            scope.$watch(attrs.ngModel, function(n) {
                if (!n) return;
                $http({
                    method: 'POST',
                    url: '/api/dataservices/' + attrs.ensureUnique,
                    data: { 'field': attrs.ensureUnique }
                }).success(function(data) {
                    c.$setValidity('unique', data.isUnique);
                }).error(function(data) {
                    c.$setValidity('unique', false);
                });
            });
        }

        return {
            restrict: 'A',
            require: 'ngModel',
            link: link
        };
    };

    ensureUnique.$inject = injectParams;

    angular.module('app').directive('ensureUnique', ensureUnique);

}());