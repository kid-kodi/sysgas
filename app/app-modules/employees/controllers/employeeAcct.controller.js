(function () {

    var injectParams = ['$window', '$scope', '$location', 'employeeService', '$timeout', '$routeParams', 'config_map'];

    var employeeAcctController = function ($window, $scope, $location, employeeService, $timeout, $routeParams, config_map) {
        var vm = this;

        var employeeId  = ($routeParams.pid) ? parseInt($routeParams.pid) : 0;
        var accountId = ($routeParams.cid) ? parseInt($routeParams.cid) : 0;

        vm.account = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;

        vm.roles = config_map.roles;

        vm.saveAccount = function () {
            console.log("saving account...");
            console.log(vm.account);

            if (accountId > 0 && accountId) {
                //alert();
                console.log('updating commande....');
                //employeeService.updateCommande(vm.commande);

                employeeService.employee.update_acct(vm.account)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }
            else {
                //employeeService.account.save_cmd(vm.commande);
                employeeService.employee.save_acct(vm.account)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }
        };

        function processResponse(result) {
            var status = result.status;
            if (!status) {
                vm.message = "une erreur est survenu!";
            }
            else {
                vm.message = "Opération effectuée !";
                vm.account = {};
                $location.path('/employee');
            }
            vm.updateStatus = true;
            startTimer();
        }

        function startTimer() {
            var timer = $timeout(function () {
                $timeout.cancel(timer);
                vm.message = '';
                vm.updateStatus = false;

                //redirction to liste
            }, 5000);
        };

        (function () {
            vm.account = {};

            if (employeeId > 0) {
                employeeService.getAccount(employeeId).then(function (result) {
                    console.log("get account");
                    console.log(result);
                    vm.account = result.account;
                    //vm.account = result.account;
                    accountId = vm.account.id;
                    console.log("vm account");
                    console.log(vm.account);
                });
            }

            if (accountId > 0) {
                vm.title = 'Modifier un compte';
                vm.buttonText = 'Modifier';
            } else {
                vm.title = 'Créer un compte';
                vm.buttonText = 'Enregistrer';
            }
        })();
           
    };

    employeeAcctController.$inject = injectParams;

    angular.module('app').controller('employeeAcctController', employeeAcctController);

})();