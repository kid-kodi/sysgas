(function () {

    var injectParams = ['$scope', '$filter', '$location', 'commandeService', 'commande_list'];

    var commandeListeController = function ($scope, $filter, $location, commandeService, commande_list) {
        var vm = this;
        vm.filteredCount = 0;
        vm.commandes = commande_list.commandes;
        vm.TotalCount = commande_list.totalCount;
        vm.TotalPages = commande_list.totalPages;

        vm.totalAttente = commande_list.totalAttente;
        vm.totalPaye = commande_list.totalPaye;
        vm.totalAnnule = commande_list.totalAnnule;

        vm.nbreAttente = commande_list.nbreAttente;
        vm.nbrePaye = commande_list.nbrePaye;
        vm.nbreAnnule = commande_list.nbreAnnule;

        vm.montantAttente = commande_list.totalAttente;
        vm.montantPaye = commande_list.totalPaye;
        vm.montantAnnule = commande_list.totalAnnule;

        vm.pageSize = 50;
        vm.pageNumber = 1;

        vm.filteredcommande = [];
        vm.searchText = null;

        vm.selectedIndex = 0;
        vm.cmd_range = 1;
        vm.isWaiting = false;

        vm.selectedTab = 1;

        vm.of = vm.pageNumber;
        vm.to = (vm.TotalCount < vm.pageSize) ? vm.TotalCount : vm.pageSize;

        vm.selectedStateId = 1;
        vm.showSearchForm = false;

        filterCommandes("", vm.selectedStateId, vm.cmd_range);

        vm.toggleSeachForm = function () {
            vm.showSearchForm = !vm.showSearchForm;
        };

        vm.cmdChange = function () {
            var searchText = vm.searchText;
            if (searchText = null) { searchText = ''; }
            filterCommandes(searchText, vm.selectedStateId, vm.cmd_range);
        };

        vm.onStateChange = function (state_id) {
            vm.selectedStateId = state_id;
            vm.selectedTab = state_id;
            var searchText = vm.searchText;
            if (searchText = null) { searchText = ''; }
            filterCommandes(searchText, vm.selectedStateId, vm.cmd_range);
        };

        vm.searchTextChanged = function () {
            filterCommandes(vm.searchText, vm.selectedStateId);
        };


        vm.itemClicked = function (commande_id) {
            vm.selectedIndex = commande_id;
            for (var i = 0; i < vm.commandes.length; i++) {
                if (vm.commandes[i].currentStateId == 1 && vm.commandes[i].id == commande_id) {
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
                cmd_range : vm.cmd_range,
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText: vm.searchText
            }
            var commande_list =
                commandeService.commande.init(arg_map)
                .then(function (commande_list) {
                    vm.commandes = commande_list.commandes;
                    filterCommandes('', vm.selectedStateId);
                });
            //console.log(commande_list);
            return commande_list;
        };

        vm.lookUp = function () {
            var arg_map = {
                cmd_range:vm.cmd_range,
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText: vm.searchText
            }
            var commande_list =
                commandeService.commande.init(arg_map)
                .then(function (commande_list) {
                    vm.commandes = commande_list.commandes;

                    vm.filteredCount = 0;
                    vm.TotalCount = commande_list.totalCount;
                    vm.TotalPages = commande_list.totalPages;

                    vm.totalAttente = commande_list.totalAttente;
                    vm.totalPaye = commande_list.totalPaye;
                    vm.totalAnnule = commande_list.totalAnnule;

                    vm.nbreAttente = commande_list.nbreAttente;
                    vm.nbrePaye = commande_list.nbrePaye;
                    vm.nbreAnnule = commande_list.nbreAnnule;

                    vm.montantAttente = commande_list.totalAttente;
                    vm.montantPaye = commande_list.totalPaye;
                    vm.montantAnnule = commande_list.totalAnnule;

                    filterCommandes(vm.searchText, vm.selectedStateId, vm.cmd_range);
                });
            //console.log(commande_list);
            return commande_list;
        };

        vm.refreshList = function () {
            var arg_map = {
                cmd_range : vm.cmd_range,
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText: ""
            }
            var commande_list =
            commandeService.commande.init(arg_map)
            .then(function (commande_list) {
                vm.commandes = commande_list.commandes;

                filterCommandes('', vm.selectedStateId);
            });
        };

        vm.valideCommande = function (commande_id) {
            commandeService.commande.valide(commande_id)
                .then(function (result) {
                    $location.path('/commande/'+commande_id);
            });
        };

        function filterCommandes(filterText, stateId, cmd_range) {
            if (vm.commandes.length > 0) {
                vm.filteredcommande = $filter("filterCommande")(vm.commandes, filterText, stateId, cmd_range);
                vm.filteredCount = vm.filteredcommande.length;
            }
            vm.init = true;
            vm.selectedIndex = 0;
            vm.isWaiting = false;
        }

        (function () {
            vm.pageSize = commandeService.pageSize;
            vm.pageNumber = commandeService.pageNumber;
            vm.searchText = "";

            $scope.$on('onconnected', function () {
                alert('conn');
            });
            filterCommandes('', vm.selectedStateId, vm.cmd_range);

        })();

        setMontant = function () {
            vm.montantAttente = 0;
            vm.montantPaye = 0;
            vm.montantAnnule = 0;

            for (var i = 0; i < vm.commandes.length; i++) {
                var commande = vm.commandes[i];
                if (commande.currentStateId == 1) {
                    vm.montantAttente = vm.montantAttente + commande.netAPaye;
                }
                if (commande.currentStateId == 2) {
                    vm.montantPaye = vm.montantPaye + commande.netAPaye;
                }
                if (commande.currentStateId == 3) {
                    vm.montantAnnule = vm.montantAnnule + commande.netAPaye;
                }
            }

        };

        

    };

    commandeListeController.$inject = injectParams;

    angular.module('app').controller('commandeListeController', commandeListeController);

})();