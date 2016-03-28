(function () {

    var injectParams = ['$scope', '$filter', 'departementService','departement_list'];

    var departementListeController = function ($scope, $filter, departementService, departement_list) {
        var vm = this;
        vm.departements = departement_list.departements;
        vm.TotalCount = departement_list.totalCount;
        vm.TotalPages = departement_list.totalPages;

        vm.filteredDepartement = [];
        vm.searchText = null;
        vm.showSearchForm = false;

        vm.selectedIndex = 0;
        vm.pageSize = 50;
        vm.pageNumber = 1;

        
        vm.of = vm.pageNumber;
        vm.to = vm.to = (vm.TotalCount < vm.pageSize) ? vm.TotalCount : vm.pageSize;

        filterDepartements("");

        vm.toggleSeachForm = function () {
            vm.showSearchForm = !vm.showSearchForm;
        };


        vm.searchTextChanged = function () {
            filterDepartements(vm.searchText);
        };


        vm.itemClicked = function (departement_id) {
            vm.selectedIndex = departement_id;
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
            var departement_list =
                departementService.departement.init(arg_map)
                .then(function (departement_list) {
                    vm.departements = departement_list.departements;
                    filterDepartements('');
                });
            //console.log(departement_list);
            return departement_list;
        };

        vm.lookUp = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText : vm.searchText
            }
            var departement_list =
                departementService.departement.init(arg_map)
                .then(function (departement_list) {
                    vm.departements = departement_list.departements;
                    filterDepartements('');
                });
            //console.log(departement_list);
            return departement_list;
        };

        function filterDepartements(filterText) {
            //console.log(vm.departements);
            vm.filteredCount = vm.departements.length;

            if (vm.filteredCount > 0) {
                vm.filteredDepartement = $filter("filterDepartement")(vm.departements, filterText);
            }
            vm.init = true;
            //alert();
        }

        (function() {
            vm.pageSize = departementService.pageSize;
            vm.pageNumber = departementService.pageNumber;
            vm.searchText = "";
        })();

    };

    departementListeController.$inject = injectParams;

    angular.module('app').controller('departementListeController', departementListeController);

})();