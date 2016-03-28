(function () {

    var injectParams = ['$window', '$scope', '$location', 'employeeService', '$timeout', '$routeParams', 'config_map'];

    var employeeEditController = function ($window, $scope, $location, employeeService, $timeout, $routeParams, config_map) {
        var vm = this;

        var employeeId = ($routeParams.id) ? parseInt($routeParams.id) : 0;

        vm.employee = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;
        vm.jours = config_map.jours;
        vm.mois = config_map.mois;
        vm.annees = config_map.annees;
        vm.pays = config_map.pays;
        vm.communes = config_map.communes;
        vm.typePieceFournits = config_map.typePieceFournits;
        vm.titres = config_map.titres;
        

        vm.saveEmployee = function () {
            if (employeeId > 0) {
                employeeService.employee.edit(vm.employee)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }
            else {
                employeeService.employee.add(vm.employee)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }
            
        };

        function processResponse(result) {
            var error = result.error;
            var employee = result.employee;
            if (error) {
                vm.message = "L'employee n'as pas été enregistré";
            }
            else {
                vm.message = "Modification(s) effectuée(s) !";
                vm.employee = {};
                $location.path('/employee');
            }
            vm.updateStatus = true;
            startTimer();
        }

        (function () {
            if (employeeId > 0) {
                vm.employee.id = employeeId;
                vm.title = 'Modifier les informations';
                vm.buttonText = 'Modifier';
                employeeService.getemployee(employeeId);

                //vm.employee = employeeService.employee.get_employee(employeeId)[0];
            } else {
                vm.title = 'Enregistrer les informations';
                vm.buttonText = 'Enregistrer';
            }
        })();

        $scope.$on('app-set-employee', function (event, response) {
            console.log('set employee');
            console.log(response);

            vm.employee = response.employee;
            vm.commande_list = response.employee.commande_list;
        });


        function startTimer() {
            var timer = $timeout(function () {
                $timeout.cancel(timer);
                vm.message = '';
                vm.updateStatus = false;
            }, 5000);
        }

        $scope.$on('app-set-employee', function (event, response) {
            console.log(response);
            //vm.employee = response.employee;
            vm.employee = response.employee;
        });

    };

    employeeEditController.$inject = injectParams;

    angular.module('app').controller('employeeEditController', employeeEditController);

})();