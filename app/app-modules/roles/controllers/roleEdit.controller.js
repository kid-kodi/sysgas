(function () {

    var injectParams = ['$window', '$scope', '$location', 'roleService', '$timeout', '$routeParams', 'config_map'];

    var roleEditController = function ($window, $scope, $location, roleService, $timeout, $routeParams, config_map) {
        var vm = this;

        var roleId = ($routeParams.id) ? parseInt($routeParams.id) : 0;

        vm.role = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;
        vm.capabilities = config_map.capabilities;

        vm.selection = [];
        vm.roleName = null;

        vm.toggleSelection = function toggleSelection(capabilityId) {
            var idx = vm.selection.indexOf(capabilityId);

            // is currently selected
            if (idx > -1) {
                vm.selection.splice(idx, 1);
            }

                // is newly selected
            else {
                vm.selection.push(capabilityId);
            }

            console.log(vm.selection);
        };

        
        vm.saveRole = function () {
            
            if (roleId > 0) {
                roleService.role.edit(roleId, vm.roleName, vm.selection)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }
            else {


                roleService.role.add(vm.roleName, vm.selection)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }

        };

        function processResponse(result) {
            var error = result.error;
            var role = result.role;
            if (!error) {
                vm.message = "Le role n'as pas été enregistré";
            }
            else {
                vm.message = "Modification(s) effectuée(s) !";
                vm.role = {};
                $location.path('/role');
            }
            vm.updateStatus = true;
            startTimer();
        }

        (function () {
            if (roleId > 0) {
                vm.role.id = roleId;
                vm.title = 'Modifier les informations';
                vm.buttonText = 'Modifier';
                roleService.getrole(roleId);
            } else {
                vm.title = 'Enregistrer les informations';
                vm.buttonText = 'Enregistrer';
            }
        })();

        $scope.$on('app-set-role', function (event, response) {
            console.log('set role');
            console.log(response);

            //vm.role = response.role;
            vm.roleName = response.role.roleName;
            vm.selection = response.capabilities;
        });


        function startTimer() {
            var timer = $timeout(function () {
                $timeout.cancel(timer);
                vm.message = '';
                vm.updateStatus = false;
            }, 5000);
        }

        $scope.$on('app-set-role', function (event, response) {
            console.log(response);
            //vm.role = response.role;
            vm.role = response.role;
        });

    };

    roleEditController.$inject = injectParams;

    angular.module('app').controller('roleEditController', roleEditController);

})();