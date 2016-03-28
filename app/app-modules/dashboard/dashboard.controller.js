(function () {

    var injectParams = ['$scope', '$http'];

    var dashBoardController = function ($scope, $http) {
        var vm = this;
        vm.patient_stats;
        vm.commande_stats;

        console.log($scope.$parent.vm.pageTitle);
        $http.get('api/v1/stats').then(function (result) {
            console.log(result);
            vm.patient_stats = result.data.patient;
            vm.commande_stats = result.data.commande;
        });
    };

    dashBoardController.$inject = injectParams;

    angular.module('app')
        .controller('dashBoardController', dashBoardController);

})();