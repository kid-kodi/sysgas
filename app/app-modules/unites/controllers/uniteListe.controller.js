(function () {

    var injectParams = ['$scope', '$filter', 'uniteService','unite_list'];

    var uniteListeController = function ($scope, $filter, uniteService, unite_list) {
        var vm = this;
        vm.unites = unite_list.unites;
        vm.TotalCount = unite_list.totalCount;
        vm.TotalPages = unite_list.totalPages;

        vm.filteredUnite = [];
        vm.searchText = null;
        vm.showSearchForm = false;

        vm.selectedIndex = 0;
        vm.pageSize = 50;
        vm.pageNumber = 1;

        vm.of = vm.pageNumber;
        vm.to = (vm.TotalCount < vm.pageSize) ? vm.TotalCount : vm.pageSize;


        filterUnites("");

        vm.toggleSeachForm = function () {
            vm.showSearchForm = !vm.showSearchForm;
        };


        vm.searchTextChanged = function () {
            filterUnites(vm.searchText);
        };


        vm.itemClicked = function (unite_id) {
            vm.selectedIndex = unite_id;
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
            var unite_list =
                uniteService.unite.init(arg_map)
                .then(function (unite_list) {
                    vm.unites = unite_list.unites;
                    filterUnites('');
                });
            //console.log(unite_list);
            return unite_list;
        };

        vm.lookUp = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText : vm.searchText
            }
            var unite_list =
                uniteService.unite.init(arg_map)
                .then(function (unite_list) {
                    vm.unites = unite_list.unites;
                    filterUnites('');
                });
            //console.log(unite_list);
            return unite_list;
        };

        function filterUnites(filterText) {
            //console.log(vm.unites);
            vm.filteredCount = vm.unites.length;

            if (vm.filteredCount > 0) {
                vm.filteredUnite = $filter("filterUnite")(vm.unites, filterText);
            }
            vm.init = true;
            //alert();
        }

        (function() {
            vm.pageSize = uniteService.pageSize;
            vm.pageNumber = uniteService.pageNumber;
            vm.searchText = "";
        })();


    };

    uniteListeController.$inject = injectParams;

    angular.module('app').controller('uniteListeController', uniteListeController);

})();