(function () {
    
    var injectParams = ['$http', '$q', '$rootScope'];

    var allcommandeFactory = function ($http, $q, $rootScope) {
        var serviceBase = '/api/', allcommandeList = [],
        factory = {
            pageNumber: 1,
            pageSize: 50
        },

        configMap = { anon_id: 'a0' },

        stateMap = {
            anon_allcommande: null,
            cid_serial: 0,
            allcommande_cid_map: {},
            allcommande_db: TAFFY(),
            allcommande: {}
        },

        allcommandeProto, makeCid, clearallcommandeDb, removeallcommande,
        makeallcommande, allcommande;

        

        //var patientHub = $.connection.patientHub; // initializes hub

        //$.connection.hub.start() // starts hub
        //.done(function () {
        //    alert('connected to signal R');
        //    $rootScope.$broadcast('onconnected', [{}]);
        //}) // handle when finish
        //.fail(function () {
        //    console.error('Error connecting to signalR');
        //}); //handle error
        

        makeCid = function () {
            return 'c' + String(stateMap.cid_serial++);
        };

        clearallcommandeDb = function () {
            var allcommande = stateMap.allcommande;
            stateMap.allcommande_db = TAFFY();
            stateMap.allcommande_cid_map = {};
            if (allcommande) {
                stateMap.allcommande_db.insert(allcommande);
                stateMap.allcommande_cid_map[allcommande.cid] = allcommande;
            }
        };

        removeallcommande = function (allcommande) {
            if (!allcommande) { return false; }
            // can't remove anonymous person
            if (allcommande.id === configMap.anon_id) {
                return false;
            }
            stateMap.allcommande_db({ cid: allcommande.cid }).remove();
            if (allcommande.cid) {
                delete stateMap.allcommande_cid_map[allcommande.cid];
            }
            return true;
        };

        allcommandeProto = {
            get_is_user: function () {
                return this.cid === stateMap.allcommande.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_allcommande.cid;
            }
        };

        makeallcommande = function (allcommande_map) {
            var allcommande,
            cid = allcommande_map.cid,
            id = allcommande_map.id,
            nom = allcommande_map.nom,
            prenom = allcommande_map.prenom,
            genre = allcommande_map.genre,
            jourNaissance = allcommande_map.jourNaissance,
            moisNaissance = allcommande_map.moisNaissance,
            anneeNaissance = allcommande_map.anneeNaissance,
            adresse = allcommande_map.adresse,
            email = allcommande_map.email,
            telephone = allcommande_map.telephone,
            paysId = allcommande_map.paysId,
            paysLib = allcommande_map.paysLib,
            typePieceFournitId = allcommande_map.typePieceFournitId,
            typePieceFournitLib = allcommande_map.typePieceFournitLib,
            numeroPiece = allcommande_map.numeroPiece,
            allcommande_list = allcommande_map.allcommande_list,
            insertDate = allcommande_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'allcommande id and nom required';
            }

            allcommande = Object.create(allcommandeProto);
            allcommande.cid = cid;
            allcommande.nom = nom;
            allcommande.prenom = prenom;
            allcommande.genre = genre;
            allcommande.jourNaissance = jourNaissance;
            allcommande.moisNaissance = moisNaissance;
            allcommande.anneeNaissance = anneeNaissance;
            allcommande.adresse = adresse;
            allcommande.email = email;
            allcommande.telephone = telephone;
            allcommande.paysId = paysId;
            allcommande.paysLib = paysLib,
            allcommande.typePieceFournitId = typePieceFournitId;
            allcommande.typePieceFournitLib = typePieceFournitLib;
            allcommande.numeroPiece = numeroPiece;
            allcommande.allcommande_list = allcommande_list;
            allcommande.insertDate = insertDate;

            if (id) { allcommande.id = id; }

            stateMap.allcommande_cid_map[cid] = allcommande;
            stateMap.allcommande_db.insert(allcommande);
            return allcommande;
        };

        factory.allcommande = (function () {
            var init, get_by_cid, get_db, get_allcommande, _add, _update_list, queryArgs, valide,
                _publish_listchange, _join, _leave, save_cmd, get_by_id, update_cmd;

            queryArgs = {
                pageSize: 50,
                pageNumber: 1
            }

            _update_list = function (arg_list) {
                var i, allcommande_map, make_allcommande_map,
                //allcommande_list = arg_list[0];
                allcommande_list = arg_list;
                clearallcommandeDb();
                allcommande:
                    for (i = 0; i < allcommande_list.length; i++) {
                        allcommande_map = allcommande_list[i];
                        if (!allcommande_map.nom) { continue allcommande; }
                        make_allcommande_map = {
                            cid: makeCid(),
                            id: allcommande_map.id,
                            nom: allcommande_map.nom,
                            prenom: allcommande_map.prenom,
                            genre: allcommande_map.genre,
                            jourNaissance: allcommande_map.jourNaissance,
                            moisNaissance: allcommande_map.moisNaissance,
                            anneeNaissance: allcommande_map.anneeNaissance,
                            adresse: allcommande_map.adresse,
                            email: allcommande_map.email,
                            telephone: allcommande_map.telephone,
                            paysId: allcommande_map.paysId,
                            paysLib: allcommande_map.paysLib,
                            typePieceFournitId: allcommande_map.typePieceFournitId,
                            typePieceFournitLib: allcommande_map.typePieceFournitLib,
                            numeroPiece: allcommande_map.numeroPiece,
                            allcommande_list: allcommande_map.allcommande_list,
                            insertDate: allcommande_map.insertDate
                        };
                        makeallcommande(make_allcommande_map);
                    }
                stateMap.allcommande_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-allcommandelistchange', arg_list);
            };

            _add = function (allcommande) {
                return $http.post(serviceBase + 'postallcommande', allcommande).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-allcommande-added', response);
                    return response;
                });
            };

            _edit = function (allcommande) {
                return $http.put(serviceBase + 'putallcommande?id=' + allcommande.id, allcommande).then(function (response) {
                    console.log("update-allcommande");
                    console.log(response);
                    $rootScope.$broadcast('app-update-allcommande', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                //GetAllallcommande(int pageSize, int pageNumber)
                pageSize = arg_map.pageSize;
                pageNumber = arg_map.pageNumber;
                searchText = arg_map.searchText;

                return $http.get(serviceBase + 'allcommande?pageSize=' + pageSize
                    + '&pageNumber=' + pageNumber
                    + '&searchText=' + searchText).then(
                function (results) {
                    console.log(results);

                    var arg_list = results.data;
                    //_update_list(arg_list);
                    return arg_list;
                });
            };

            _leave = function () { };

            save_cmd = function (cmd_map) {
                console.log('# allcommande to save #');
                console.log(cmd_map);

                return $http.post(serviceBase + 'postCommande', cmd_map)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-insert-allcommande', results);
                    });
            };

            valide = function (cmd_id) {
                console.log('# allcommande to valide #');
                console.log(cmd_id);

                return $http.put(serviceBase + 'valideCommande?id=' + cmd_id)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-update-allcommande', results);
                    });
            };

            update_cmd = function (allcommande) {
                return $http.put(serviceBase + 'putCommande?id=' + allcommande.id, allcommande)
                    .then(function (results) {
                        console.log(results);
                        return results;
                        //$rootScope.$broadcast('app-update-allcommande', results);
                    });
            };

            get_by_id = function (cmd_id) {
                console.log('# get allcommande #');
                console.log(cmd_id);

                return $http.get(serviceBase + 'getCommandeById?id=' + cmd_id)
                    .then(function (results) {
                        console.log('#get comm');
                        console.log(results);
                        return results;
                    });
            };

            get_by_cid = function (cid) {
                return stateMap.allcommande_cid_map[cid];
            };
            get_db = function () { return stateMap.allcommande_db; };

            get_allcommande = function (allcommande_id) {
                var allcommandeDB, allcommande;

                allcommandeDB = get_db();
                allcommande = allcommandeDB({ id: allcommande_id }).get();
                console.log(allcommande);
                return allcommande;
            };

            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_allcommande: get_allcommande,
                add: _add,
                edit: _edit,
                valide : valide,
                save_cmd: save_cmd,
                update_cmd: update_cmd,
                get_by_id: get_by_id,
                join: _join,
                init: init,
                leave: _leave
            };
        }());

        factory.getEditConfig = function () {
            return $http.get(serviceBase + 'editconfig').then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };

        factory.getCmdConfig = function () {
            return $http.get(serviceBase + 'cmdconfig').then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };



        factory.checkUniqueValue = function (id, property, value) {
            if (!id) id = 0;
            return $http.get(serviceBase + 'checkUnique/' + id + '?property=' + property + '&value=' + escape(value)).then(
                function (results) {
                    return results.data.status;
                });
        };

        factory.insertCommandeAnalyse = function (type_contrat_id, analyse_id, employee_id) {

            var type_contrat_id = type_contrat_id;
            var analyse_id = analyse_id;
            var employee_id = employee_id;

            console.log('selected contrat id ' + type_contrat_id);
            console.log('selected analyse id ' + analyse_id);
            return $http.get(serviceBase + 'postCommandeAnalyse?tcontratid='
                + type_contrat_id + '&analyseid='
                + analyse_id + '&employeeid='
                + employee_id).then(function (results) {
                    var response = results.data.allcommandeAnalyse;
                    $rootScope.$broadcast('app-insert-allcommandeAnalyse', response);
                    return results.data;
                });
        };

        factory.updateAnalyse = function (analyse) {
            return $http.put(serviceBase + 'putAnalyse/' + analyse.id, analyse).then(function (status) {
                $rootScope.$broadcast('app-update-analyse', results.data);
                return status.data;
            });
        };



        factory.insertallcommande = function (allcommande) {
            return $http.post(serviceBase + 'postallcommande', allcommande).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-allcommande', response);
                return response;
            });
        };

        factory.newallcommande = function () {
            return $q.when({ allcommandeId: 0 });
        };



        factory.deleteallcommande = function (id) {
            return $http.delete(serviceBase + 'deleteallcommande/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getallcommande = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'allcommandeById?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-allcommande', response);
            });
        };

        return factory;
    };

    allcommandeFactory.$inject = injectParams;

    angular.module('app').factory('allcommandeService', allcommandeFactory);

}());