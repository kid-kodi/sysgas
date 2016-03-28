(function () {

    var injectParams = ['$scope', '$routeParams', 'employeeService', '$compile', '$filter'];

    var employeeDetailsController = function ($scope, $routeParams, employeeService, $compile, $filter) {
        var vm = this,
            employeeId = ($routeParams.id) ? parseInt($routeParams.id) : 0;

        vm.employee = {};
        vm.commande = {};
        currentCommande = {};
        vm.analyses = [];
        vm.commandeAnalyse = [];
        vm.analyseList = [];
        vm.analyse = {};
        vm.isFormOpen = false;
        vm.isCommandeOpen = false;
        vm.isAnalyseOpen = false;
        vm.isInfosOpen = false;
        //vm.isSearchOpen = false;
        vm.init = false;
        vm.totalApaye = 0;
        vm.nbreBTotal = 0;
        
        vm.serviceMedecins = [];
        vm.societes = [];
        vm.filteredSocietes = [];
        vm.typeHopitals = [];
        vm.typeEmployees = [];
        vm.etablissementSanitaires = [];
        vm.employees = [];
        vm.caisses = [];

        vm.commande_list = [];
        vm.filteredCommandes = [];

        vm.nbreAttente = 0;
        vm.nbreRegle = 0;
        vm.nbreAnnule = 0;

        vm.filterCommande = function (state_id) {
            //alert(state_id);
            vm.filteredCommandes = [];
            var state_id = state_id;
            vm.filteredCommandes=$filter('filter')(vm.commande_list, { currentStateId: state_id });

            console.log(vm.filteredCommandes);
        };

        // $scope is a special object that makes
        // its properties available to the view as
        // variables. Here we set some default values:

        vm.showtooltip = (vm.commande.id > 0) ? false : true;
        vm.value = 'Edit me.';

         //Some helper functions that will be
         //available in the angular declarations

        vm.hideTooltip = function () {

            // When a model is changed, the view will be automatically
            // updated by by AngularJS. In this case it will hide the tooltip.

            vm.showtooltip = false;
        }

        vm.toggleTooltip = function (e) {
            e.stopPropagation();
            vm.showtooltip = !vm.showtooltip;
        }

        vm.ToggleForm = function (index) {
            switch (index) {
                case 'analyse':
                    vm.isInfosOpen = false;
                    vm.isAnalyseOpen = !vm.isAnalyseOpen;
                    break;
                case 'infos':
                    vm.isAnalyseOpen = false;
                    vm.isInfosOpen = !vm.isInfosOpen;
                    break;
                case 'commande':
                    vm.isCommandeOpen = !vm.isCommandeOpen;
                    break;
            }
        };

        vm.updateSociete = function (typeEmployeeId) {
            var id = typeEmployeeId;
            console.log(id);

            var societeList = [];
            //vm.contrats = [];
            vm.filteredSocietes = [];
            

            for (var i = 0; i < vm.societes.length; i++) {
                var typeEmployeeSociete = vm.societes[i].typeEmployeeSociete;
                for (var j = 0; j < typeEmployeeSociete.length; j++) {
                    if (typeEmployeeSociete[j].typeEmployeeId === id) {
                        societeList.push(vm.societes[i]);
                    }
                }
            }
            console.log('');
            console.log(societeList);
            console.log(societeList);
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

        vm.saveCommande = function () {
            console.log("saving commande ...");
            vm.commande.employeeId = employeeId;
            vm.commande.analyse_list = vm.commandeAnalyse;
            vm.commande.currentStateId = 1;
            vm.commande.submitterId = 1;

            console.log(vm.commandeAnalyse);

            if (vm.commande.id > 0 && vm.commande.id) {
                console.log('updating commande....');
                employeeService.updateCommande(vm.commande);
            }
            else {
                employeeService.employee.save_cmd(vm.commande);
            }
        };

        vm.getCommande = function (commande_id) {
            var commande_id = commande_id;
            employeeService.employee.get_commande_by_id(commande_id);
            //alert(commande_id);
        }

        $scope.$on('app-insert-commande', function (event, result) {
            //vm.commande = {};
            console.log("## -- app-insert-commande");
            console.log(result);
            var commande_added = result.data;
            vm.commande_list.push(commande_added);
            updateCommandeStatut();
            vm.isCommandeOpen = !vm.isCommandeOpen;
        });

        $scope.$on('app-set-commande', function (event, result) {
            //vm.commande = {};
            console.log("## -- app-set-commande");
            console.log(result);
            //var commande_added = result.data;
            //vm.commande_list.push(commande_added);
            //updateCommandeStatut();
            
            vm.commande = result.data.commande;
            vm.commandeAnalyse = [];
            var selected_analyse = result.data.commande.analyse_list;

            for (var i = 0; i < selected_analyse.length ; i++) {
                vm.commandeAnalyse.push(selected_analyse[i]);
            }

            vm.commande.totalApaye = vm.commande.totalNetAPayer;
            //alert(vm.commande.totalNetAPayer);
            vm.commande.nbreBTotal = vm.commande.totalNbreB;
            setMontant();

            vm.isCommandeOpen = !vm.isCommandeOpen;
            //$scope.$apply();
            
        });

        vm.updateCmd = function(commandeId) {
            vm.ToggleForm('commande');
            employeeService.getJsonCommandeById(commandeId);
        };

        vm.saveCommandeAnalyse = function () {
            var contrat_id = vm.commande.contratId;
            var employee_id = 0;
            var type_contrat_id = 0;
            var analyse_id = 0;
            for (var i = 0; i < vm.contrats.length; i++) {
                if (vm.contrats[i].id === contrat_id) {
                    type_contrat_id = vm.contrats[i].typeContratId;
                }
            }

            if (vm.analyse.employeeId != undefined) {
                employee_id = vm.analyse.employeeId;
            }

            analyse_id = vm.analyse.analyseId;

            console.log(vm.analyse.employeeId);
            employeeService.insertCommandeAnalyse(type_contrat_id, analyse_id, employee_id)
            .then(function (result) {
                console.log(result);
            });
        };

        $scope.$on('app-insert-commandeAnalyse', function (event, result) {
            console.log('commande analyse result !!!');
            console.log(result);

            //vm.commandeAnalyse = [];
            var commandeAnalyse = result;
            vm.commandeAnalyse.push(commandeAnalyse);

            setMontant();

            vm.analyse = {};
        });

        var setMontant = function () {
            vm.commande.totalNetAPayer = 0;
            vm.commande.totalNbreB = 0;

            
            for (var i = 0; i < vm.commandeAnalyse.length;i++){
                vm.commande.totalNetAPayer = vm.commande.totalNetAPayer + vm.commandeAnalyse[i].netAPayer;
                //alert(vm.commande.totalNetAPayer);
                vm.commande.totalNbreB = vm.commande.totalNbreB + vm.commandeAnalyse[i].nbreB;
            }
            //$scope.$apply();
            //alert('montant set');
        };

        $scope.$on('app-set-employee', function (event, response) {
            console.log('set employee');
            console.log(response);

            vm.employee = response.employee;
            vm.commande_list = response.employee.commande_list;

            updateCommandeStatut();
            //vm.filterCommande(1);
            vm.init = true;
        });

        $scope.$on('app-update-commande', function (event, response) {
            console.log('update commande');
            console.log(response.data.id);

            var commande = response.data;

            //vm.commandeAnalyse = [];
            //var selected_analyse = response.data.analyse_list;

            //for (var i = 0; i < selected_analyse.length ; i++) {
            //    vm.commandeAnalyse.push(selected_analyse[i]);
            //}

            //for(var i=0; i<vm.commande_list.length; i++){
            //    if (vm.commande_list[i].id == commande.id) {
            //        console.log(vm.commande_list[i].id);
            //        console.log(commande.id);
            //        //alert();
            //        //vm.commande_list[i] == commande;
            //        vm.commande_list.splice(i, 1);

            //        vm.commande_list.push(commande);
            //        vm.filterCommande(1);
            //    }
            //}

            var index = -1;
            var comArr = eval(vm.commande_list);
            for (var i = 0; i < comArr.length; i++) {
                
                if (comArr[i].id === commande.id) {
                    index = i;
                    break;
                }
            }
            if (index === -1) {
                alert("Something gone wrong");
            }
            vm.commande_list.splice(index, 1);
            console.log("### before"+vm.commande_list.length);

            vm.commande_list.push(commande);
            console.log("### after" + vm.commande_list.length);

            //setMontant();
            vm.filterCommande(1);

            vm.isCommandeOpen = !vm.isCommandeOpen;
        });

        var updateCommandeStatut = function () {
            vm.nbreAttente = 0;
            vm.nbreRegle = 0;
            vm.nbreAnnule = 0;

            for (var i = 0; i < vm.commande_list.length ; i++) {

                if (vm.commande_list[i].currentStateId == 1) {
                    vm.nbreAttente = vm.nbreAttente + 1;
                }
                if (vm.commande_list[i].currentStateId == 2) {
                    vm.nbreRegle = vm.nbreRegle + 1;
                }
                if (vm.commande_list[i].currentStateId == 3) {
                    vm.nbreAnnule = vm.nbreAnnule + 1;
                }
            }

            vm.filterCommande(1);
        }

        var setAnalyseList = function (analyses) {
            vm.analyseList = analyses;
            vm.analyses = [];
            for (var i = 0; i < vm.analyseList.length; i++) {
                vm.analyses.push(vm.analyseList[i].analyseLib);
            }
        };

        vm.addCommande = function () {
            angular.element(document.getElementById('employeeBox'))
            .append($compile("<cmd-form pId='" + employeeId + "'></cmd-form>")($scope));
        };

        (function () {
            employeeService.getEmployee(employeeId); 
            vm.init = false;
        })();

        //vm.filterCommande = function (status) {
        //    alert(status);
        //};

        vm.setAnalyse = function() {
            for (var i = 0; i < vm.analyseList.length; i++) {
                if (vm.analyse.analyseLib === vm.analyseList[i].analyseLib) {
                    vm.analyse.analyseId = vm.analyseList[i].id;
                }
            }
        };

        vm.setAnalyseForm = function (commandeAnalyseId) {
            employeeService.getCommandeAnalyse(commandeAnalyseId);
        };

        vm.delAnalyseRow = function (commandeAnalyseId) {
            //alert(commandeAnalyseId);

            var index = -1;
            var comArr = eval(vm.commandeAnalyse);
            for (var i = 0; i < comArr.length; i++) {
                if (comArr[i].id === commandeAnalyseId) {
                    index = i;
                    break;
                }
            }
            if (index === -1) {
                alert("Something gone wrong");
            }
            vm.commandeAnalyse.splice(index, 1);
            setMontant();
        };
    };

    employeeDetailsController.$inject = injectParams;

    angular.module('app')
        .controller('employeeDetailsController', employeeDetailsController);

})();