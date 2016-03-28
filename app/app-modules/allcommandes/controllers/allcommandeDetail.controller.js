/// <reference path="../template/facture.html" />
(function () {

    var injectParams = ['$window', '$scope', '$location', 'allcommandeService', '$timeout', '$routeParams', 'printer'];

    var allcommandeDetailController = function ($window, $scope, $location, allcommandeService, $timeout, $routeParams, printer) {
        var vm = this;

        var allcommandeId = ($routeParams.cid) ? parseInt($routeParams.cid) : 0;

        vm.allcommande = {};
        vm.patient = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;
        vm.init = false;

        vm.printFacture = function (allcommande_id) {
            printer.print("app/allcommandes/template/facture.html", vm.allcommande);
        };

        vm.valideAllcommande = function (allcommande_id) {
            allcommandeService.allcommande.valide(allcommande_id)
                .then(function (result) {
                    var allcommande = result.data.allcommande;
                    vm.allcommande = allcommande;
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
                $location.path('/allcommande');
            }
            vm.updateStatus = true;
            startTimer();
        }

        (function () {
            if (allcommandeId > 0) {
                vm.title = 'Modifier allcommande';
                vm.buttonText = 'Modifier';
                allcommandeService.allcommande.get_by_id(allcommandeId)
                    .then(function (result) {
                        var commande = result.data.commande;
                        vm.allcommande = commande;
                        vm.patient = commande.patient;
                        vm.init = true;
                });
            } else {
                vm.title = 'Nouvelle allcommande';
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

    allcommandeDetailController.$inject = injectParams;

    angular.module('app').controller('allcommandeDetailController', allcommandeDetailController);

})();