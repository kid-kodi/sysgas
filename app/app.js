(function () {
    'use strict';

    angular
        .module('app', ['ngRoute', 'ngCookies', 'angular-loading-bar', 'ngAnimate', 'angularMoment', 'autocomplete'])
        .config(config)
        .run(run);

    config.$inject = ['$routeProvider', '$locationProvider', 'cfpLoadingBarProvider'];
    function config($routeProvider, $locationProvider, cfpLoadingBarProvider) {

        //loading bar configuration
        cfpLoadingBarProvider.includeBar = false;

        //spinner bar configuration
        cfpLoadingBarProvider.includeSpinner = true;
        //spinner template
        cfpLoadingBarProvider.spinnerTemplate = '<div id="loading-bar-spinner"><span class="icon ion-loading-c"></span><span>chargement...</span></div>';

        var viewBase = 'app/app-modules/';

        //$httpProvider.interceptors.push('authInterceptor');

        $routeProvider
            .when('/login', {
                controller: 'LoginController',
                templateUrl: viewBase + 'login/login.view.html',
                controllerAs: 'vm'
            })
            .when('/dashboard', {
                controller: 'dashBoardController',
                templateUrl: viewBase + 'dashboard/dashboard.view.html',
                controllerAs: 'vm',
                title: 'Dashboard',
                secure: true
            })
            .when('/patient', {
                templateUrl: viewBase + 'patients/views/patientliste.html',
                controller: 'patientListeController',
                controllerAs: 'vm',
                title: 'Liste des patients',
                resolve: {
                    patient_list: ['patientService', function (patientService) {
                        var arg_map = {
                            pageSize: patientService.pageSize,
                            pageNumber: patientService.pageNumber,
                            searchText: '_'
                        }
                        var patient_list = patientService.patient.init(arg_map);
                        return patient_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/patientedit/:id', {
                templateUrl: viewBase + 'patients/views/patientedit.html',
                controller: 'patientEditController',
                controllerAs: 'vm',
                title: 'Fomulaire d\'un patient',
                resolve: {
                    config_map: ['patientService', function (patientService) {
                        return patientService.getEditConfig().then(function (result) {
                            var config_map = result;
                            return config_map;
                        });
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/patientcmd/:pid/:cid', {
                templateUrl: viewBase + 'patients/views/patientcmd.html',
                controller: 'patientCmdController',
                controllerAs: 'vm',
                title: 'Commande d\'un patient',
                resolve: {
                    config_map: ['patientService', function (patientService) {
                        return patientService.getCmdConfig().then(function (result) {
                            var config_map = result;
                            return config_map;
                        });
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/commandeanalyse/:cid', {
                templateUrl: viewBase + 'patients/views/commande.analyse.html',
                controller: 'CommandeAnalyseController',
                controllerAs: 'vm',
                title: 'Commande d\'un patient',
                resolve: {
                    config_map: ['patientService', function (patientService) {
                        return patientService.getCmdConfig().then(function (result) {
                            var config_map = result;
                            return config_map;
                        });
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/patient/:id', {
                templateUrl: viewBase + 'patients/views/patientdetail.html',
                controller: 'patientDetailsController',
                controllerAs: 'vm',
                title: 'Détails d\'un patient',
                secure: true //This route requires an authenticated user
            })
            .when('/commande', {
                templateUrl: viewBase + 'commandes/views/commandeliste.html',
                controller: 'commandeListeController',
                controllerAs: 'vm',
                resolve: {
                    commande_list: ['commandeService', function (commandeService) {
                        var arg_map = {
                            cmd_range: commandeService.cmd_range,
                            pageSize: commandeService.pageSize,
                            pageNumber: commandeService.pageNumber,
                            searchText: ''
                        }
                        var commande_list = commandeService.commande.init(arg_map);
                        return commande_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/commande/:cid', {
                templateUrl: viewBase + 'commandes/views/commandedetail.html',
                controller: 'commandeDetailController',
                controllerAs: 'vm',
                secure: true //This route requires an authenticated user
            })
            .when('/facture', {
                templateUrl: viewBase + 'factures/views/factureliste.html',
                controller: 'factureListeController',
                controllerAs: 'vm',
                resolve: {
                    facture_list: ['factureService', function (factureService) {
                        var arg_map = {
                            pageSize: factureService.pageSize,
                            pageNumber: factureService.pageNumber,
                            searchText: '',
                            employeeId: 0,
                            laboratoireId: 0,
                            numeroFacture: '',
                            date: ''
                        }
                        var facture_list = factureService.facture.init(arg_map);
                        return facture_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/employee', {
                templateUrl: viewBase + 'employees/views/employeeliste.html',
                controller: 'employeeListeController',
                controllerAs: 'vm',
                resolve: {
                    employee_list: ['employeeService', function (employeeService) {
                        var arg_map = {
                            pageSize: employeeService.pageSize,
                            pageNumber: employeeService.pageNumber,
                            searchText: ''
                        }
                        var employee_list = employeeService.employee.init(arg_map);
                        return employee_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/employeeedit/:id', {
                templateUrl: viewBase + 'employees/views/employeeedit.html',
                controller: 'employeeEditController',
                controllerAs: 'vm',
                resolve: {
                    config_map: ['employeeService', function (employeeService) {
                        return employeeService.getEditConfig().then(function (result) {
                            var config_map = result;
                            return config_map;
                        });
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/employeeacct/:pid/:cid', {
                templateUrl: viewBase + 'employees/views/employeeacct.html',
                controller: 'employeeAcctController',
                controllerAs: 'vm',
                resolve: {
                    config_map: ['employeeService', function (employeeService) {
                        return employeeService.getAcctConfig().then(function (result) {
                            var config_map = result;
                            return config_map;
                        });
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/role', {
                templateUrl: viewBase + 'roles/views/roleliste.html',
                controller: 'roleListeController',
                controllerAs: 'vm',
                resolve: {
                    role_list: ['roleService', function (roleService) {
                        var arg_map = {
                            pageSize: roleService.pageSize,
                            pageNumber: roleService.pageNumber,
                            searchText: ''
                        }
                        var role_list = roleService.role.init(arg_map);
                        return role_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/roleedit/:id', {
                templateUrl: viewBase + 'roles/views/roleedit.html',
                controller: 'roleEditController',
                controllerAs: 'vm',
                resolve: {
                    config_map: ['roleService', function (roleService) {
                        return roleService.getEditConfig().then(function (result) {
                            var config_map = result;
                            return config_map;
                        });
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/societe', {
                templateUrl: viewBase + 'societes/views/societeliste.html',
                controller: 'societeListeController',
                controllerAs: 'vm',
                resolve: {
                    societe_list: ['societeService', function (societeService) {
                        var arg_map = {
                            pageSize: societeService.pageSize,
                            pageNumber: societeService.pageNumber,
                            searchText: ''
                        }
                        var societe_list = societeService.societe.init(arg_map);
                        return societe_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/societeedit/:id', {
                templateUrl: viewBase + 'societes/views/societeedit.html',
                controller: 'societeEditController',
                controllerAs: 'vm',
                secure: true //This route requires an authenticated user
            })
            .when('/societecontrat/:id', {
                templateUrl: viewBase + 'societes/views/societecontrat.html',
                controller: 'societeContratcontroller',
                controllerAs: 'vm',
                resolve: {
                    config_map: ['societeService', function (societeService) {
                        return societeService.getEditConfig().then(function (result) {
                            var config_map = result;
                            return config_map;
                        });
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/departement', {
                templateUrl: viewBase + 'departements/views/departementliste.html',
                controller: 'departementListeController',
                controllerAs: 'vm',
                resolve: {
                    departement_list: ['departementService', function (departementService) {
                        var arg_map = {
                            pageSize: departementService.pageSize,
                            pageNumber: departementService.pageNumber,
                            searchText: ''
                        }
                        var departement_list = departementService.departement.init(arg_map);
                        return departement_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/departementedit/:id', {
                templateUrl: viewBase + 'departements/views/departementedit.html',
                controller: 'departementEditController',
                controllerAs: 'vm',
                resolve: {
                    config_map: ['departementService', function (departementService) {
                        return departementService.getEditConfig().then(function (result) {
                            var config_map = result;
                            return config_map;
                        });
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/analyse', {
                templateUrl: viewBase + 'analyses/views/analyseliste.html',
                controller: 'analyseListeController',
                controllerAs: 'vm',
                resolve: {
                    analyse_list: ['analyseService', function (analyseService) {
                        var arg_map = {
                            pageSize: analyseService.pageSize,
                            pageNumber: analyseService.pageNumber,
                            searchText: ''
                        }
                        var analyse_list = analyseService.analyse.init(arg_map);
                        return analyse_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/analyseedit/:id', {
                templateUrl: viewBase + 'analyses/views/analyseedit.html',
                controller: 'analyseEditController',
                controllerAs: 'vm',
                resolve: {
                    config_map: ['analyseService', function (analyseService) {
                        return analyseService.getEditConfig().then(function (result) {
                            var config_map = result;
                            return config_map;
                        });
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/unite', {
                templateUrl: viewBase + 'unites/views/uniteliste.html',
                controller: 'uniteListeController',
                controllerAs: 'vm',
                resolve: {
                    unite_list: ['uniteService', function (uniteService) {
                        var arg_map = {
                            pageSize: uniteService.pageSize,
                            pageNumber: uniteService.pageNumber,
                            searchText: ''
                        }
                        var unite_list = uniteService.unite.init(arg_map);
                        return unite_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/uniteedit/:id', {
                templateUrl: viewBase + 'unites/views/uniteedit.html',
                controller: 'uniteEditController',
                controllerAs: 'vm',
                resolve: {
                    config_map: ['uniteService', function (uniteService) {
                        return uniteService.getEditConfig().then(function (result) {
                            var config_map = result;
                            return config_map;
                        });
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/laboratoire', {
                templateUrl: viewBase + 'laboratoires/views/laboratoireliste.html',
                controller: 'laboratoireListeController',
                controllerAs: 'vm',
                resolve: {
                    laboratoire_list: ['laboratoireService', function (laboratoireService) {
                        var arg_map = {
                            pageSize: laboratoireService.pageSize,
                            pageNumber: laboratoireService.pageNumber,
                            searchText: ''
                        }
                        var laboratoire_list = laboratoireService.laboratoire.init(arg_map);
                        return laboratoire_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/laboratoireedit/:id', {
                templateUrl: viewBase + 'laboratoires/views/laboratoireedit.html',
                controller: 'laboratoireEditController',
                controllerAs: 'vm',
                resolve: {
                    config_map: ['laboratoireService', function (laboratoireService) {
                        return laboratoireService.getEditConfig().then(function (result) {
                            var config_map = result;
                            return config_map;
                        });
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/mescommande', {
                templateUrl: viewBase + 'mescommandes/views/mescommandeliste.html',
                controller: 'mescommandeListeController',
                controllerAs: 'vm',
                resolve: {
                    mescommande_list: ['mescommandeService', function (mescommandeService) {
                        var arg_map = {
                            pageSize: mescommandeService.pageSize,
                            pageNumber: mescommandeService.pageNumber,
                            searchText: ''
                        }
                        var mescommande_list = mescommandeService.mescommande.init(arg_map);
                        return mescommande_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/mescommande/:cid', {
                templateUrl: viewBase + 'mescommandes/views/mescommandedetail.html',
                controller: 'mescommandeDetailController',
                controllerAs: 'vm',
                secure: true //This route requires an authenticated user
            })
            .when('/allcommande', {
                templateUrl: viewBase + 'allcommandes/views/allcommandeliste.html',
                controller: 'allcommandeListeController',
                controllerAs: 'vm',
                resolve: {
                    allcommande_list: ['allcommandeService', function (allcommandeService) {
                        var arg_map = {
                            pageSize: allcommandeService.pageSize,
                            pageNumber: allcommandeService.pageNumber,
                            searchText: ''
                        }
                        var allcommande_list = allcommandeService.allcommande.init(arg_map);
                        return allcommande_list;
                    }]
                },
                secure: true //This route requires an authenticated user
            })
            .when('/allcommande/:cid', {
                templateUrl: viewBase + 'allcommandes/views/allcommandedetail.html',
                controller: 'allcommandeDetailController',
                controllerAs: 'vm',
                secure: true //This route requires an authenticated user
            })
            .otherwise({ redirectTo: '/dashboard' });
    }

    run.$inject = ['$rootScope', '$location', '$cookieStore', '$http', 'amMoment'];
    function run($rootScope, $location, $cookieStore, $http, amMoment) {

        angular.element(document).on("click", function(e) {
            $rootScope.$broadcast("documentClicked", angular.element(e.target));
        });

        amMoment.changeLocale('fr');
        // keep user logged in after page refresh
        $rootScope.globals = $cookieStore.get('globals') || {};
        if ($rootScope.globals.currentUser) {
            $http.defaults.headers.common['Authorization'] = $rootScope.globals.currentUser.authdata; // jshint ignore:line
        }

        $rootScope.$on("$routeChangeStart", function (event, next, current) {
            if (next && next.$$route && next.$$route.secure) {
                if ($rootScope.globals.currentUser == null) {
                    $rootScope.$evalAsync(function () {
                        $location.path('/login');
                    });
                }
            }
        });
    }

})();