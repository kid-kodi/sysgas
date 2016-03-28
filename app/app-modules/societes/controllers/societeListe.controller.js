(function () {

    var injectParams = ['$scope', '$filter', 'societeService','societe_list'];

    var societeListeController = function ($scope, $filter, societeService, societe_list) {
        var vm = this;
        vm.societes = societe_list.societes;
        vm.TotalCount = societe_list.totalCount;
        vm.TotalPages = societe_list.totalPages;

        vm.filteredSociete = [];
        vm.searchText = null;
        vm.showSearchForm = false;

        vm.selectedIndex = 0;
        vm.pageSize = 50;
        vm.pageNumber = 1;

        vm.of = vm.pageNumber;
        vm.to = (vm.TotalCount < vm.pageSize) ? vm.TotalCount : vm.pageSize;


        filterSocietes("");

        vm.toggleSeachForm = function () {
            vm.showSearchForm = !vm.showSearchForm;
        };


        vm.searchTextChanged = function () {
            filterSocietes(vm.searchText);
        };


        vm.itemClicked = function (societe_id) {
            vm.selectedIndex = societe_id;
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
            var societe_list =
                societeService.societe.init(arg_map)
                .then(function (societe_list) {
                    vm.societes = societe_list.societes;
                    filterSocietes('');
                });
            //console.log(societe_list);
            return societe_list;
        };

        vm.lookUp = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText : vm.searchText
            }
            var societe_list =
                societeService.societe.init(arg_map)
                .then(function (societe_list) {
                    vm.societes = societe_list.societes;
                    filterSocietes('');
                });
            //console.log(societe_list);
            return societe_list;
        };

        function filterSocietes(filterText) {
            //console.log(vm.societes);
            vm.filteredCount = vm.societes.length;

            if (vm.filteredCount > 0) {
                vm.filteredSociete = $filter("filterSociete")(vm.societes, filterText);
            }
            vm.init = true;
            //alert();
        }

        (function() {
            vm.pageSize = societeService.pageSize;
            vm.pageNumber = societeService.pageNumber;
            vm.searchText = "";
        })();

    };

    societeListeController.$inject = injectParams;

    angular.module('app').controller('societeListeController', societeListeController);

})();