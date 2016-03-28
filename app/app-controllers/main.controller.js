(function () {

    var injectParams = ['$rootScope', '$location', 'AuthenticationService'];

    var mainController = function ($rootScope, $location, AuthenticationService) {
        var vm = this,
            appTitle = 'SYSGAS';

        vm.currentUser = $rootScope.globals.currentUser;
        vm.appTitle = appTitle;
        vm.IsCard = false;
        vm.pageTitle = "";

        vm.toggleCard = function () {
            vm.IsCard = !vm.IsCard;
        };

        vm.highlight = function (path) {
            return $location.path().substr(0, path.length) === path;
        };

        vm.loginOrOut = function () {
            AuthenticationService.ClearCredentials();
            $location.path('/login');
        };

        $rootScope.$on('loginOrOut', function (event, user) {
            vm.currentUser = user;
        });

        $rootScope.$on('$routeChangeSuccess', function (event, data) {
            vm.pageTitle = data.title;
        });

    };

    mainController.$inject = injectParams;

    angular.module('app').controller('mainController', mainController);

}());
