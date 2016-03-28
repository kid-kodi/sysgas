(function () {

    var injectParams = ['$scope', '$filter', '$location', 'mescommandeService', 'mescommande_list'];

    var mescommandeListeController = function ($scope, $filter, $location, mescommandeService, mescommande_list) {
        var vm = this;
        vm.filteredCount = 0;
        vm.mescommandes = mescommande_list.mescommandes;
        vm.TotalCount = mescommande_list.totalCount;
        vm.TotalPages = mescommande_list.totalPages;

        vm.totalAttente = mescommande_list.totalAttente;
        vm.totalPaye = mescommande_list.totalPaye;
        vm.totalAnnule = mescommande_list.totalAnnule;

        vm.nbreAttente = mescommande_list.nbreAttente;
        vm.nbrePaye = mescommande_list.nbrePaye;
        vm.nbreAnnule = mescommande_list.nbreAnnule;

        vm.montantAttente = mescommande_list.totalAttente;
        vm.montantPaye = mescommande_list.totalPaye;
        vm.montantAnnule = mescommande_list.totalAnnule;

        vm.pageSize = 50;
        vm.pageNumber = 1;
        vm.of = vm.pageNumber;
        vm.to = vm.pageSize;

        vm.filteredMescommande = [];
        vm.searchText = null;

        vm.selectedIndex = 0;
        vm.selectedStateId = 1;
        vm.isWaiting = false;

        vm.selectedTab = 1;

        filterMescommandes("", vm.selectedStateId);

        vm.onStateChange = function (state_id) {
            vm.selectedStateId = state_id;
            vm.selectedTab = state_id;
            var searchText = vm.searchText;
            if (searchText = null) { searchText = ''; }
            filterMescommandes(searchText, vm.selectedStateId);
        };

        vm.searchTextChanged = function () {
            filterMescommandes(vm.searchText, vm.selectedStateId);
        };


        vm.itemClicked = function (mescommande_id) {
            vm.selectedIndex = mescommande_id;
            for (var i = 0; i < vm.mescommandes.length; i++) {
                if (vm.mescommandes[i].currentStateId == 1 && vm.mescommandes[i].id == mescommande_id) {
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
            var mescommande_list =
                mescommandeService.patient.init(arg_map)
                .then(function (mescommande_list) {
                    vm.mescommandes = mescommande_list.mescommandes;
                    filterMescommandes('', vm.selectedStateId);
                });
            //console.log(mescommande_list);
            return mescommande_list;
        };

        vm.lookUp = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText: vm.searchText
            }
            var mescommande_list =
                mescommandeService.mescommande.init(arg_map)
                .then(function (mescommande_list) {
                    vm.mescommandes = mescommande_list.mescommandes;
                    filterMescommandes('', vm.selectedStateId);
                });
            //console.log(mescommande_list);
            return mescommande_list;
        };

        vm.refreshList = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText: ""
            }
            var mescommande_list =
            mescommandeService.mescommande.init(arg_map)
            .then(function (mescommande_list) {
                vm.mescommandes = mescommande_list.mescommandes;
                filterMescommandes('', vm.selectedStateId);
            });
        };

        vm.valideMescommande = function (mescommande_id) {
            mescommandeService.mescommande.valide(mescommande_id)
                .then(function (result) {
                    $location.path('/mescommande/'+mescommande_id);
            });
        };

        function filterMescommandes(filterText, stateId) {
            if (vm.mescommandes.length > 0) {
                vm.filteredMescommande = $filter("filterMescommande")(vm.mescommandes, filterText, stateId);
                vm.filteredCount = vm.filteredMescommande.length;
                //alert(vm.filteredMescommande.length);
            }
            vm.init = true;
            vm.selectedIndex = 0;
            vm.isWaiting = false;
        }

        (function () {
            vm.pageSize = mescommandeService.pageSize;
            vm.pageNumber = mescommandeService.pageNumber;
            vm.searchText = "";

            $scope.$on('onconnected', function () {
                alert('conn');
            });
            filterMescommandes('', vm.selectedStateId);

        })();

        setMontant = function () {
            vm.montantAttente = 0;
            vm.montantPaye = 0;
            vm.montantAnnule = 0;

            for (var i = 0; i < vm.mescommandes.length; i++) {
                var mescommande = vm.mescommandes[i];
                if (mescommande.currentStateId == 1) {
                    vm.montantAttente = vm.montantAttente + mescommande.netAPaye;
                }
                if (mescommande.currentStateId == 2) {
                    vm.montantPaye = vm.montantPaye + mescommande.netAPaye;
                }
                if (mescommande.currentStateId == 3) {
                    vm.montantAnnule = vm.montantAnnule + mescommande.netAPaye;
                }
            }

        };

        

    };

    mescommandeListeController.$inject = injectParams;

    angular.module('app').controller('mescommandeListeController', mescommandeListeController);

})();