(function () {

    var injectParams = [];

    var ngFocus = function () {
        
        var FOCUS_CLASS = "ng-focused";
        var link = function(scope, element, attrs, ctrl) {
            ctrl.$focused = false;
            element.bind('focus', function(evt) {
                element.addClass(FOCUS_CLASS);
                scope.$apply(function() {
                    ctrl.$focused = true;
                });
            }).bind('blur', function(evt) {
                element.removeClass(FOCUS_CLASS);
                scope.$apply(function () {
                    ctrl.$focused = false;
                });
            });
        }

        return {
            restrict: 'A',
            require: 'ngModel',
            link: link
        };
    };

    ngFocus.$inject = injectParams;

    angular.module('app').directive('ngFocus', ngFocus);

}());