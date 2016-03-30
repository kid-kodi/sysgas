(function () {

    var injectParams = ['$window', '$scope', '$location', 'patientService', '$timeout', '$routeParams', 'config_map', 'FlashService'];

    var CommandeAnalyseController = function ($window, $scope, $location, patientService, $timeout, $routeParams, config_map, FlashService) {
        var vm = this;

        var commandeId = ($routeParams.cid) ? parseInt($routeParams.cid) : 0;

        currentCommande = {};
        vm.analyses = [];
        vm.commandeAnalyse = [];
        //vm.analyseList = [];
        vm.analyse = {};
        vm.totalApaye = 0;
        vm.nbreBTotal = 0;

        vm.commande_list = [];
        vm.filteredCommandes = [];

        vm.patient = {};
        vm.commande = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;

        vm.typePatients = config_map.typePatients;
        vm.societes = config_map.societes;
        vm.contrats = config_map.contrats;
        vm.etablissementSanitaire = config_map.etablissementSanitaire;
        vm.caisses = config_map.caisseUser;
        vm.employees = config_map.employees;
        vm.analyseList = config_map.analyses;
        vm.serviceMedecins = config_map.serviceMedecins;

        vm.nbreAnalyse = 0;

        vm.submitted = false;
        vm.sending = false;

        vm.changeLocation = function( url ){
            $location.path( url );
            return false;
        }


        vm.updateSociete = function (typePatientId) {
            var id = typePatientId;
            console.log(id);

            var societeList = [];
            //vm.contrats = [];
            vm.filteredSocietes = [];


            for (var i = 0; i < vm.societes.length; i++) {
                var typePatientSociete = vm.societes[i].typePatientSociete;
                for (var j = 0; j < typePatientSociete.length; j++) {
                    if (typePatientSociete[j].typePatientId === id) {
                        societeList.push(vm.societes[i]);
                    }
                }
            }
            vm.filteredSocietes = societeList;
            vm.updateContrat(vm.filteredSocietes[0].id);
        }

        vm.updateContrat = function (societeId) {
            var id = societeId;
            console.log(id);

            for (var i = 0; i < vm.societes.length; i++) {
                if (vm.societes[i].id === id) {
                    vm.contrats = vm.societes[i].contrats;
                    console.log('');
                    console.log(vm.contrats);
                }
            }
        }

        vm.save = function () {
            
            vm.sending = true;
            if ($scope.commandeForm.$valid) { // Submit as normal 

                

                if (vm.commandeAnalyse.length > 0) {

                    vm.commande.patientId = patientId;
                    vm.commande.analyse_list = vm.commandeAnalyse;
                    vm.commande.currentStateId = 1;
                    vm.commande.submitterId = 1;

                    //console.log(vm.commandeAnalyse);

                    if (vm.commande.id > 0 && vm.commande.id) {
                        console.log('updating commande....');
                        //patientService.updateCommande(vm.commande);

                        patientService.patient.update_analyse_list(vm.commande)
                        .then(function (result) {
                            console.log(result);
                            processResponse(result);
                        });
                    }
                    else {
                        //patientService.patient.save_cmd(vm.commande);
                        patientService.patient.save_analyse_list(vm.commande)
                        .then(function (result) {
                            console.log(result);
                            processResponse(result);
                        });
                    }

                    
                }
                else {
                    FlashService.Error("Veuillez choisir au moin une analyse!");
                }

                
                vm.sending = false;
            }
            else {
                vm.submitted = true;
                vm.sending = false;
            }

            
        };

        

        vm.getCommande = function (commande_id) {
            var commande_id = commande_id;
            patientService.patient.get_commande_by_id(commande_id);
            //alert(commande_id);
        }

        vm.saveCommandeAnalyse = function () {
            
            var employee_id = 0;
            var type_contrat_id = vm.commande.typeContratId;
            var commandeId = vm.commande.id;
            var analyse_id = 0;

            if (vm.analyse.employeeId != undefined) {
                employee_id = vm.analyse.employeeId;
            }

            analyse_id = vm.analyse.analyseId;

            patientService.insertCommandeAnalyse(commandeId, type_contrat_id, analyse_id, employee_id)
            .then(function (response) {
                var error = response.error;
                if(error){ return false;}

                var commande_analyse = response.commandeanalyse;
                var commande = response.commande;

                vm.commande = commande;
                vm.commandeAnalyse.push(commande_analyse);
                vm.analyse = {};
            });
        };

        $scope.$on('app-insert-commandeAnalyse', function (event, result) {
            console.log('commande analyse result !!!');
            console.log(result);

            //vm.commandeAnalyse = [];
            var commandeAnalyse = result;
            vm.commandeAnalyse.push(commandeAnalyse);

            setMontant();
            vm.nbreAnalyse = vm.commandeAnalyse.length;
            vm.analyse = {};
        });

        var setMontant = function () {
            vm.commande.totalNetAPayer = 0;
            vm.commande.totalNbreB = 0;
            vm.nbreAnalyse = vm.commandeAnalyse.length;

            for (var i = 0; i < vm.commandeAnalyse.length; i++) {
                vm.commande.totalNetAPayer = vm.commande.totalNetAPayer + vm.commandeAnalyse[i].netAPayer;
                //alert(vm.commande.totalNetAPayer);
                vm.commande.totalNbreB = vm.commande.totalNbreB + vm.commandeAnalyse[i].nbreB;
            }
            //$scope.$apply();
            //alert('montant set');
        };

        var setAnalyseList = function () {
            vm.analyses = [];
            for (var i = 0; i < vm.analyseList.length; i++) {
                vm.analyses.push(vm.analyseList[i].analyseLib);
            }
        };

        

        function processResponse(result) {
            var status = result.status;
            if (!status) {
                vm.message = "une erreur est survenu!";
            }
            else {
                vm.message = "Opération effectuée !";
                vm.patient = {};
                $location.path('/patient/'+patientId);
            }
            vm.updateStatus = true;
            startTimer();
        }

        function startTimer() {
            var timer = $timeout(function () {
                $timeout.cancel(timer);
                vm.message = '';
                vm.updateStatus = false;

                //redirction to liste
            }, 5000);
        }

        vm.setAnalyse = function () {
            for (var i = 0; i < vm.analyseList.length; i++) {
                if (vm.analyse.analyseLib === vm.analyseList[i].analyseLib) {
                    vm.analyse.analyseId = vm.analyseList[i].id;
                }
            }
        };

       

        vm.delAnalyseRow = function (commandeAnalyseId) {
            //alert(commandeAnalyseId);
            if(commandeAnalyseId == null){
                return false;
            }

            patientService.deleteCommandeAnalyse(commandeAnalyseId)
            .then(function( response ){
                console.log( response );
                var error = response.error;
                if(error){ return false;}

                var commande = response.commande;
                vm.commande = commande;
                vm.analyse = {};

                var index = -1;
                var comArr = eval(vm.commandeAnalyse);
                for (var i = 0; i < comArr.length; i++) {
                    if (comArr[i].id === commandeAnalyseId) {
                        index = i;
                        vm.commandeAnalyse.splice(index, 1);
                        return false;
                    }
                }
                if (index === -1) {
                    alert("Something gone wrong");
                }
                

            });
        };

        (function () {
            vm.commande = {};

            if (commandeId > 0) {
                patientService.getAnalysesByCommandeId(commandeId);
                vm.title = 'Modifier ';
                vm.buttonText = 'Modifier';
            } else {
                vm.title = 'Enregistrer ';
                vm.buttonText = 'Enregistrer';
            }
        })();

        $scope.$on('app-set-analyse', function (event, response) {
            //alert('analyses set');
            vm.commande = response.commande;
            vm.commandeAnalyse = response.commandeanalyses;
            console.log(vm.commandeAnalyse);
        });

        $scope.$on('app-set-patient', function (event, response) {
            console.log('set patient');
            console.log(response);

            vm.patient = response.patient;

            if (commandeId > 0) {

                vm.commande_list = response.commande_list;


                for (var i = 0; i < vm.commande_list.length; i++) {
                    if (vm.commande_list[i].id == commandeId) {
                        vm.commande = vm.commande_list[i];
                        console.log('societeId == ' + vm.commande.societeId);
                        var typePatientId = vm.commande.typePatientId;
                        var societeId = vm.commande.societeId;
                        vm.updateSociete(typePatientId);
                        vm.updateContrat(societeId);
                    }
                }

                vm.commandeAnalyse = [];
                var selected_analyse = vm.commande.analyse_list;
                vm.nbreAnalyse = selected_analyse.length;

                for (var i = 0; i < vm.nbreAnalyse ; i++) {
                    vm.commandeAnalyse.push(selected_analyse[i]);
                }

                vm.commande.totalApaye = vm.commande.totalNetAPayer;
                //alert(vm.commande.totalNetAPayer);
                vm.commande.nbreBTotal = vm.commande.totalNbreB;
                setMontant();
            }
           

        });

        setAnalyseList();

    };

    CommandeAnalyseController.$inject = injectParams;

    angular.module('app').controller('CommandeAnalyseController', CommandeAnalyseController);

})();