(function () {

    var injectParams = ['$scope', '$filter', 'analyseService','analyse_list'];

    var analyseListeController = function ($scope, $filter, analyseService, analyse_list) {
        var vm = this;
        vm.analyses = analyse_list.analyses;
        vm.TotalCount = analyse_list.totalCount;
        vm.TotalPages = analyse_list.totalPages;

        vm.filteredAnalyse = [];
        vm.searchText = null;
        vm.showSearchForm  = false;

        vm.selectedIndex = 0;
        vm.pageSize = 50;
        vm.pageNumber = 1;

        vm.of = vm.pageNumber;
        vm.to = vm.to = (vm.TotalCount < vm.pageSize) ? vm.TotalCount : vm.pageSize;


        filterAnalyses("");

        vm.toggleSeachForm = function () {
            vm.showSearchForm = !vm.showSearchForm;
        };


        vm.searchTextChanged = function () {
            filterAnalyses(vm.searchText);
        };


        vm.itemClicked = function (analyse_id) {
            vm.selectedIndex = analyse_id;
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
            var analyse_list =
                analyseService.analyse.init(arg_map)
                .then(function (analyse_list) {
                    vm.analyses = analyse_list.analyses;
                    filterAnalyses('');
                });
            //console.log(analyse_list);
            return analyse_list;
        };

        vm.lookUp = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText : vm.searchText
            }
            var analyse_list =
                analyseService.analyse.init(arg_map)
                .then(function (analyse_list) {
                    vm.analyses = analyse_list.analyses;
                    filterAnalyses('');
                });
            //console.log(analyse_list);
            return analyse_list;
        };

        function filterAnalyses(filterText) {
            //console.log(vm.analyses);
            vm.filteredCount = vm.analyses.length;

            if (vm.filteredCount > 0) {
                vm.filteredAnalyse = $filter("filterAnalyse")(vm.analyses, filterText);
            }
            vm.init = true;
            //alert();
        }

        (function() {
            vm.pageSize = analyseService.pageSize;
            vm.pageNumber = analyseService.pageNumber;
            vm.searchText = "";
        })();

    };

    analyseListeController.$inject = injectParams;

    angular.module('app').controller('analyseListeController', analyseListeController);

})();