(function () {

    var injectParams = ['$scope', '$filter', '$location', 'allcommandeService', 'allcommande_list'];

    var allcommandeListeController = function ($scope, $filter, $location, allcommandeService, allcommande_list) {
        var vm = this;
        vm.filteredCount = 0;
        vm.allcommandes = allcommande_list.allcommandes;
        vm.TotalCount = allcommande_list.totalCount;
        vm.TotalPages = allcommande_list.totalPages;

        vm.totalAttente = allcommande_list.totalAttente;
        vm.totalPaye = allcommande_list.totalPaye;
        vm.totalAnnule = allcommande_list.totalAnnule;

        vm.nbreAttente = allcommande_list.nbreAttente;
        vm.nbrePaye = allcommande_list.nbrePaye;
        vm.nbreAnnule = allcommande_list.nbreAnnule;

        vm.montantAttente = allcommande_list.totalAttente;
        vm.montantPaye = allcommande_list.totalPaye;
        vm.montantAnnule = allcommande_list.totalAnnule;

        vm.pageSize = 50;
        vm.pageNumber = 1;

        vm.filteredallcommande = [];
        vm.searchText = null;

        vm.selectedIndex = 0;
        vm.selectedStateId = 1;
        vm.isWaiting = false;

        vm.selectedTab = 1;

        vm.of = vm.pageNumber;
        vm.to = vm.pageSize;

        filterAllcommandes("", vm.selectedStateId);

        vm.onStateChange = function (state_id) {
            vm.selectedStateId = state_id;
            vm.selectedTab = state_id;
            var searchText = vm.searchText;
            if (searchText = null) { searchText = ''; }
            filterAllcommandes(searchText, vm.selectedStateId);
        };

        vm.searchTextChanged = function () {
            filterAllcommandes(vm.searchText, vm.selectedStateId);
        };


        vm.itemClicked = function (allcommande_id) {
            vm.selectedIndex = allcommande_id;
            for (var i = 0; i < vm.allcommandes.length; i++) {
                if (vm.allcommandes[i].currentStateId == 1 && vm.allcommandes[i].id == allcommande_id) {
                    vm.isWaiting = true;
                }
            }
            
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
            var allcommande_list =
                allcommandeService.allcommande.init(arg_map)
                .then(function (allcommande_list) {
                    vm.allcommandes = allcommande_list.allcommandes;
                    filterAllcommandes('', vm.selectedStateId);
                });
            //console.log(allcommande_list);
            return allcommande_list;
        };

        vm.lookUp = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText: vm.searchText
            }
            var allcommande_list =
                allcommandeService.allcommande.init(arg_map)
                .then(function (allcommande_list) {
                    vm.allcommandes = allcommande_list.allcommandes;
                    filterAllcommandes('', vm.selectedStateId);
                });
            //console.log(allcommande_list);
            return allcommande_list;
        };

        vm.refreshList = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText: ""
            }
            var allcommande_list =
            allcommandeService.allcommande.init(arg_map)
            .then(function (allcommande_list) {
                vm.allcommandes = allcommande_list.allcommandes;
                filterAllcommandes('', vm.selectedStateId);
            });
        };

        vm.valideAllcommande = function (allcommande_id) {
            allcommandeService.allcommande.valide(allcommande_id)
                .then(function (result) {
                    $location.path('/allcommande/'+allcommande_id);
            });
        };

        function filterAllcommandes(filterText, stateId) {
            if (vm.allcommandes.length > 0) {
                vm.filteredallcommande = $filter("filterAllcommande")(vm.allcommandes, filterText, stateId);
                vm.filteredCount = vm.filteredallcommande.length;
                //alert(vm.filteredallcommande.length);
            }
            vm.init = true;
            vm.selectedIndex = 0;
            vm.isWaiting = false;
        }

        (function () {
            vm.pageSize = allcommandeService.pageSize;
            vm.pageNumber = allcommandeService.pageNumber;
            vm.searchText = "";

            $scope.$on('onconnected', function () {
                alert('conn');
            });
            filterAllcommandes('', vm.selectedStateId);

        })();

        setMontant = function () {
            vm.montantAttente = 0;
            vm.montantPaye = 0;
            vm.montantAnnule = 0;

            for (var i = 0; i < vm.allcommandes.length; i++) {
                var allcommande = vm.allcommandes[i];
                if (allcommande.currentStateId == 1) {
                    vm.montantAttente = vm.montantAttente + allcommande.netAPaye;
                }
                if (allcommande.currentStateId == 2) {
                    vm.montantPaye = vm.montantPaye + allcommande.netAPaye;
                }
                if (allcommande.currentStateId == 3) {
                    vm.montantAnnule = vm.montantAnnule + allcommande.netAPaye;
                }
            }

        };

        

    };

    allcommandeListeController.$inject = injectParams;

    angular.module('app').controller('allcommandeListeController', allcommandeListeController);

})();