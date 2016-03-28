/// <reference path="../template/facture.html" />
(function () {

    var injectParams = ['$window', '$scope', '$location', 'mescommandeService', '$timeout', '$routeParams', 'printer'];

    var mescommandeDetailController = function ($window, $scope, $location, mescommandeService, $timeout, $routeParams, printer) {
        var vm = this;

        var mescommandeId = ($routeParams.cid) ? parseInt($routeParams.cid) : 0;

        vm.commande = {};
        vm.patient = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;
        vm.init = false;

        vm.printFacture = function (mescommande_id) {
            printer.print("app/core/mescommandes/template/facture.html", vm.commande);
        };

        vm.valideMescommande = function (mescommande_id) {
            mescommandeService.mescommande.valide(mescommande_id)
                .then(function (result) {
                    var mescommande = result.data.mescommande;
                    vm.commande = mescommande;
                    console.log(result);
                    return false;
                });
        };

        function processResponse(result) {
            var status = result.status;
            var patient = result.patient;
            if (!status) {
                vm.message = "une erreur est survenu!";
            }
            else {
                vm.message = "Modification(s) effectuée(s) !";
                vm.patient = {};
                $location.path('/mescommande');
            }
            vm.updateStatus = true;
            startTimer();
        }

        (function () {
            if (mescommandeId > 0) {
                vm.title = 'Modifier mescommande';
                vm.buttonText = 'Modifier';
                mescommandeService.mescommande.get_by_id(mescommandeId)
                    .then(function (result) {
                        var commande = result.data.commande;
                        vm.commande = commande;
                        vm.patient = commande.patient;
                        vm.init = true;
                });
            } else {
                vm.title = 'Nouvelle mescommande';
                vm.buttonText = 'Enregistrer';
            }
        })();


        function startTimer() {
            var timer = $timeout(function () {
                $timeout.cancel(timer);
                vm.message = '';
                vm.updateStatus = false;
            }, 5000);
        }

        

        //patientService.initialization();
    };

    mescommandeDetailController.$inject = injectParams;

    angular.module('app').controller('mescommandeDetailController', mescommandeDetailController);

})();