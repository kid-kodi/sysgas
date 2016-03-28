(function () {

    var injectParams = ['$window', '$scope', '$location', 'analyseService', '$timeout', '$routeParams', 'config_map'];

    var analyseEditController = function ($window, $scope, $location, analyseService, $timeout, $routeParams, config_map) {
        var vm = this;

        var analyseId = ($routeParams.id) ? parseInt($routeParams.id) : 0;

        vm.analyse = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;
        vm.laboratoires = config_map.laboratoires;
        vm.departements = config_map.departements;
        vm.unites = config_map.unites;
        vm.echantillons = config_map.echantillons;

        vm.uniteList = vm.unites;
        vm.laboratoireList = vm.laboratoires;

        vm.departementId = null;
        vm.uniteId = null;
        
        vm.saveAnalyse = function () {
            
            if (analyseId > 0) {
                analyseService.analyse.edit(analyseId, vm.analyse)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }
            else {


                analyseService.analyse.add(vm.analyse)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }

        };

        function processResponse(result) {
            var error = result.error;
            var analyse = result.analyse;
            if (error) {
                vm.message = "Le analyse n'as pas été enregistré";
            }
            else {
                vm.message = "Modification(s) effectuée(s) !";
                vm.analyse = {};
                $location.path('/analyse');
            }
            vm.updateStatus = true;
            startTimer();
        }

        (function () {
            if (analyseId > 0) {
                vm.analyse.id = analyseId;
                vm.title = 'Modifier les informations';
                vm.buttonText = 'Modifier';
                analyseService.getanalyse(analyseId);
            } else {
                vm.title = 'Enregistrer les informations';
                vm.buttonText = 'Enregistrer';
            }
        })();

        vm.onDeptChange = function() {
            //set unite List
            var deptId = vm.departementId;
            setUniteByDeptId(deptId);
        }
        vm.onUniteChange = function() {
            //set laboratoire List
            var uniteId = vm.uniteId;
            setLabByUniteId(uniteId);
        }

        function getLaboratoire(laboId) {
            var laboratoire = {};
            console.log(laboId);

            for (var i = 0; i < vm.laboratoires.length; i++) {
                console.log(vm.laboratoires[i]);
                if (vm.laboratoires[i].id == laboId) {
                    laboratoire = vm.laboratoires[i];
                }
            }

            return laboratoire;
        }

        function getUnite(uniteId) {
            var unite = {};

            for (var i = 0; i < vm.unites.length; i++) {
                if (vm.unites[i].id === uniteId) {
                    unite = vm.unites[i];
                }
            }

            return unite;
        }

        function setUniteByDeptId(deptId) {
            vm.uniteList = [];
            vm.laboratoireList = [];
            for (var i = 0; i < vm.unites.length; i++) {
                if (vm.unites[i].departementId === deptId) {
                    vm.uniteList.push(vm.unites[i]);
                }
            }
        }

        function setLabByUniteId(uniteId) {
            vm.laboratoireList = [];
            for (var i = 0; i < vm.laboratoires.length; i++) {
                if (vm.laboratoires[i].uniteId === uniteId) {
                    vm.laboratoireList.push(vm.laboratoires[i]);
                }
            }
        }

        function getDepartement(deptId) {
            var departement = {};

            for (var i = 0; i < vm.departements.length; i++) {
                if (vm.departements[i].id === deptId) {
                    departement = vm.departements[i];
                }
            }

            return departement;
        }


        function startTimer() {
            var timer = $timeout(function () {
                $timeout.cancel(timer);
                vm.message = '';
                vm.updateStatus = false;
            }, 5000);
        }

        $scope.$on('app-set-analyse', function (event, response) {

            console.log(response);
            //vm.analyse = response.analyse;
            vm.analyse = response.analyse;

            var departement = {};
            var unite = {};
            var laboratoire = {};


            var laboratoireId = vm.analyse.laboratoireId;

            laboratoire = getLaboratoire(laboratoireId);

            console.log(laboratoire);
            //console.log(vm.unites);

            unite = getUnite(laboratoire.uniteId);
            departement = getDepartement(unite.departementId);


            vm.departementId = departement.id;
            vm.uniteId = unite.id;
        });

    };

    analyseEditController.$inject = injectParams;

    angular.module('app').controller('analyseEditController', analyseEditController);

})();