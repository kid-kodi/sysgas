(function () {

    var injectParams = ['$scope', '$filter', 'employeeService','employee_list'];

    var employeeListeController = function ($scope, $filter, employeeService, employee_list) {
        var vm = this;
        vm.employees = employee_list.employees;
        vm.TotalCount = employee_list.totalCount;
        vm.TotalPages = employee_list.totalPages;

        vm.filteredEmployee = [];
        vm.searchText = null;

        vm.selectedIndex = 0;
        vm.pageSize = 50;
        vm.pageNumber = 1;

        vm.of = vm.pageNumber;
        vm.to = vm.pageSize;

        vm.showSearchForm = false;


        filterEmployees("");

        vm.toggleSeachForm = function () {
            vm.showSearchForm = !vm.showSearchForm;
        };


        vm.searchTextChanged = function () {
            filterEmployees(vm.searchText);
        };


        vm.itemClicked = function (employee_id) {
            vm.selectedIndex = employee_id;
        };

        vm.navigateTo = function (direction) {

            if (direction == 'next') {
                vm.of = vm.of + vm.pageSize;
                vm.to = vm.to + vm.pageSize;
                vm.pageNumber = vm.pageNumber + 1;
            }
            else {
                if (vm.pageNumber == 0) { return false; }
                vm.pageNumber = vm.pageNumber - 1;
                vm.of = vm.of - vm.pageSize;
                vm.to = vm.to - vm.pageSize;
            }
            
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText: vm.searchText
            }
            var employee_list =
                employeeService.employee.init(arg_map)
                .then(function (employee_list) {
                    vm.employees = employee_list.employees;
                    filterEmployees('');
                });
            //console.log(employee_list);
            return employee_list;
        };

        vm.lookUp = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText : vm.searchText
            }
            var employee_list =
                employeeService.employee.init(arg_map)
                .then(function (employee_list) {
                    vm.employees = employee_list.employees;
                    filterEmployees('');
                });
            //console.log(employee_list);
            return employee_list;
        };

        function filterEmployees(filterText) {
            //console.log(vm.employees);
            vm.filteredCount = vm.employees.length;

            if (vm.filteredCount > 0) {
                vm.filteredEmployee = $filter("filterEmployee")(vm.employees, filterText);
            }
            vm.init = true;
            //alert();
        }

        (function() {
            vm.pageSize = employeeService.pageSize;
            vm.pageNumber = employeeService.pageNumber;
            vm.searchText = "";
        })();

    };

    employeeListeController.$inject = injectParams;

    angular.module('app').controller('employeeListeController', employeeListeController);

})();