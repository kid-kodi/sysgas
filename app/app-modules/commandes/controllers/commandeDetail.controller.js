/// <reference path="../template/facture.html" />
(function () {

    var injectParams = ['$window', '$scope', '$location', 'commandeService', '$timeout', '$routeParams', 'printer'];

    var commandeDetailController = function ($window, $scope, $location, commandeService, $timeout, $routeParams, printer) {
        var vm = this;

        var commandeId = ($routeParams.cid) ? parseInt($routeParams.cid) : 0;

        vm.commande = {};
        vm.patient = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;
        vm.init = false;

        vm.printFacture = function (commande_id) {
            var print_obj_map = {
                patient  : vm.patient,
                commande : vm.commande
            }
            printer.print("app/app-modules/commandes/template/facture.html", print_obj_map);
        };

        vm.valideCommande = function (commande_id) {
            commandeService.commande.valide(commande_id)
                .then(function (result) {
                    vm.commande = result.data.commande;
                    vm.patient  = result.data.patient;
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
                $location.path('/commande');
            }
            vm.updateStatus = true;
            startTimer();
        }

        (function () {
            if (commandeId > 0) {
                vm.title = 'Modifier commande';
                vm.buttonText = 'Modifier';
                commandeService.commande.get_by_id(commandeId)
                    .then(function (result) {
                        //var commande = result.data.commande;
                        vm.commande = result.data.commande;
                        vm.patient  = result.data.patient;
                        vm.init = true;
                });
            } else {
                vm.title = 'Nouvelle commande';
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

    commandeDetailController.$inject = injectParams;

    angular.module('app').controller('commandeDetailController', commandeDetailController);

})();