(function () {

    var injectParams = ['$scope', '$filter', 'roleService','role_list'];

    var roleListeController = function ($scope, $filter, roleService, role_list) {
        var vm = this;
        vm.roles = role_list.roles;
        vm.TotalCount = role_list.totalCount;
        vm.TotalPages = role_list.totalPages;

        vm.filteredRole = [];
        vm.searchText = null;

        vm.selectedIndex = 0;
        vm.pageSize = 50;
        vm.pageNumber = 1;

        vm.of = vm.pageNumber;
        vm.to = (vm.TotalCount < vm.pageSize) ? vm.TotalCount : vm.pageSize;
        vm.showSearchForm = false;


        filterRoles("");
        
        vm.toggleSeachForm = function () {
            vm.showSearchForm = !vm.showSearchForm;
        };


        vm.searchTextChanged = function () {
            filterRoles(vm.searchText);
        };


        vm.itemClicked = function (role_id) {
            vm.selectedIndex = role_id;
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
            var role_list =
                roleService.role.init(arg_map)
                .then(function (role_list) {
                    vm.roles = role_list.roles;
                    filterRoles('');
                });
            //console.log(role_list);
            return role_list;
        };

        vm.lookUp = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText : vm.searchText
            }
            var role_list =
                roleService.role.init(arg_map)
                .then(function (role_list) {
                    vm.roles = role_list.roles;
                    filterRoles('');
                });
            //console.log(role_list);
            return role_list;
        };

        function filterRoles(filterText) {
            //console.log(vm.roles);
            vm.filteredCount = vm.roles.length;

            if (vm.filteredCount > 0) {
                vm.filteredRole = $filter("filterRole")(vm.roles, filterText);
            }
            vm.init = true;
            //alert();
        }

        (function() {
            vm.pageSize = roleService.pageSize;
            vm.pageNumber = roleService.pageNumber;
            vm.searchText = "";
        })();

    };

    roleListeController.$inject = injectParams;

    angular.module('app').controller('roleListeController', roleListeController);

})();