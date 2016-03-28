(function () {

    var injectParams = [];

    var maxInput = function () {
        
        var FOCUS_CLASS = "ng-focused";
        var link = function(scope, element, attrs, ctrl) {
            
        }

        return {
            restrict: 'A',
            require: 'ngModel',
            link: link
        };
    };

    maxInput.$inject = injectParams;

    angular.module('app').directive('maxInput', maxInput);

}());