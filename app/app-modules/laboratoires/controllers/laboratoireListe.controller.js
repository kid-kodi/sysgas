(function () {

    var injectParams = ['$scope', '$filter', 'laboratoireService','laboratoire_list'];

    var laboratoireListeController = function ($scope, $filter, laboratoireService, laboratoire_list) {
        var vm = this;
        vm.laboratoires = laboratoire_list.laboratoires;
        vm.TotalCount = laboratoire_list.totalCount;
        vm.TotalPages = laboratoire_list.totalPages;

        vm.filteredLaboratoire = [];
        vm.searchText = null;
        vm.showSearchForm = false;

        vm.selectedIndex = 0;
        vm.pageSize = 50;
        vm.pageNumber = 1;

        vm.of = vm.pageNumber;
        vm.to = (vm.TotalCount < vm.pageSize) ? vm.TotalCount : vm.pageSize;


        filterLaboratoires("");

        vm.toggleSeachForm = function () {
            vm.showSearchForm = !vm.showSearchForm;
        };


        vm.searchTextChanged = function () {
            filterLaboratoires(vm.searchText);
        };


        vm.itemClicked = function (laboratoire_id) {
            vm.selectedIndex = laboratoire_id;
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
            var laboratoire_list =
                laboratoireService.laboratoire.init(arg_map)
                .then(function (laboratoire_list) {
                    vm.laboratoires = laboratoire_list.laboratoires;
                    filterLaboratoires('');
                });
            //console.log(laboratoire_list);
            return laboratoire_list;
        };

        vm.lookUp = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText : vm.searchText
            }
            var laboratoire_list =
                laboratoireService.laboratoire.init(arg_map)
                .then(function (laboratoire_list) {
                    vm.laboratoires = laboratoire_list.laboratoires;
                    filterLaboratoires('');
                });
            //console.log(laboratoire_list);
            return laboratoire_list;
        };

        function filterLaboratoires(filterText) {
            //console.log(vm.laboratoires);
            vm.filteredCount = vm.laboratoires.length;

            if (vm.filteredCount > 0) {
                vm.filteredLaboratoire = $filter("filterLaboratoire")(vm.laboratoires, filterText);
            }
            vm.init = true;
            //alert();
        }

        (function() {
            vm.pageSize = laboratoireService.pageSize;
            vm.pageNumber = laboratoireService.pageNumber;
            vm.searchText = "";
        })();


    };

    laboratoireListeController.$inject = injectParams;

    angular.module('app').controller('laboratoireListeController', laboratoireListeController);

})();