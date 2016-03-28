(function () {

    var injectParams = ['$scope', '$filter', 'factureService', 'facture_list', 'printer'];

    var factureListeController = function ($scope, $filter, factureService, facture_list, printer) {
        var vm = this;
        vm.totalPaye = facture_list.totalPaye;
        vm.factures = facture_list.factures;
        vm.TotalCount = facture_list.totalCount;
        vm.TotalPages = facture_list.totalPages;
        vm.userCaisse = facture_list.caisseUser;
        vm.laboratoires = facture_list.laboratoires;
        vm.jours = facture_list.jours;
        vm.mois = facture_list.mois;
        vm.annees = facture_list.annees;

        vm.filteredFacture = [];
        vm.searchText = null;

        vm.selectedIndex = 0;
        vm.pageSize = 50;
        vm.pageNumber = 1;
        vm.laboratoireId = 0;
        vm.employeeId = 0;
        vm.numeroFacture = '';
        vm.date = '';

        vm.showForm = false;

        vm.of = vm.pageNumber;
        vm.to = (vm.TotalCount < vm.pageSize) ? vm.TotalCount : vm.pageSize;
        vm.showSearchForm = false;

        /*hub = $.connection.factureHub;
        $.connection.hub.start().done(function () {
            console.log('app started');
        });*/

        vm.printFacture = function () {
            console.log(vm.filteredFacture);

            var total = vm.totalPaye;
            
            printer.print("app/app-modules/factures/template/facture.html", {
                factures: vm.filteredFacture,
                total : total
            });
        };


        filterfactures("");

        vm.toggleSeachForm = function () {
            vm.showSearchForm = !vm.showSearchForm;
        };

        vm.toggleClass = function () {
            vm.showForm = !vm.showForm;
        };

        vm.searchTextChanged = function () {
            filterfactures(vm.searchText);
        };


        vm.itemClicked = function (facture_id) {
            vm.selectedIndex = facture_id;
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

            if (vm.anneeNumber != '' && vm.moisNumber != '' && vm.jourNumber != '') {
                alert();
                vm.date = vm.anneeNumber + '-' + vm.moisNumber + '-' + vm.jourNumber;
            }

            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText: vm.searchText,
                laboratoireId: vm.laboratoireId,
                employeeId: vm.employeeId,
                numeroFacture: vm.numeroFacture,
                date: vm.date
        }
            var facture_list =
                factureService.facture.init(arg_map)
                .then(function (facture_list) {
                    vm.factures = facture_list.factures;

                    vm.totalPaye = facture_list.totalPaye;
                    vm.TotalCount = facture_list.totalCount;
                    vm.TotalPages = facture_list.totalPages;
                    vm.userCaisse = facture_list.userCaisse;

                    filterfactures('');
                });
            //console.log(facture_list);
            return facture_list;
        };

        vm.lookUp = function () {

            if (vm.anneeNumber != '' && vm.moisNumber != '' && vm.jourNumber != '') {
                vm.date = vm.anneeNumber + '-' + vm.moisNumber + '-' + vm.jourNumber;
            }

            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText: vm.searchText,
                laboratoireId: vm.laboratoireId,
                employeeId: vm.employeeId,
                numeroFacture: vm.numeroFacture,
                date: vm.date
            }
            var facture_list =
                factureService.facture.init(arg_map)
                .then(function (facture_list) {
                    vm.factures = facture_list.factures;
                    vm.totalPaye = facture_list.totalPaye;
                    vm.TotalCount = facture_list.totalCount;
                    vm.TotalPages = facture_list.totalPages;
                    vm.userCaisse = facture_list.userCaisse;
                    filterfactures('');
                });
            //console.log(facture_list);
            return facture_list;
        };

        function filterfactures(filterText) {
            //console.log(vm.factures);
            vm.filteredCount = vm.factures.length;

            if (vm.filteredCount > 0) {
                vm.filteredFacture = $filter("filterFacture")(vm.factures, filterText);
            }
            vm.init = true;
            vm.showForm = false;
            //alert();
        }

        (function() {
            vm.pageSize = factureService.pageSize;
            vm.pageNumber = factureService.pageNumber;
            vm.searchText = "";
            filterfactures("");
        })();

    };

    factureListeController.$inject = injectParams;

    angular.module('app').controller('factureListeController', factureListeController);

})();