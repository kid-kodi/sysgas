(function () {
    
    var injectParams = ['$http', '$q', '$rootScope'];

    var mescommandeFactory = function ($http, $q, $rootScope) {
        var serviceBase = '/api/', mescommandeList = [],
        factory = {
            pageNumber: 1,
            pageSize: 50
        },

        configMap = { anon_id: 'a0' },

        stateMap = {
            anon_mescommande: null,
            cid_serial: 0,
            mescommande_cid_map: {},
            mescommande_db: TAFFY(),
            mescommande: {}
        },

        mescommandeProto, makeCid, clearmescommandeDb, removemescommande,
        makemescommande, mescommande;

        

        /*var patientHub = $.connection.patientHub; // initializes hub

        $.connection.hub.start() // starts hub
        .done(function () {
            alert('connected to signal R');
            $rootScope.$broadcast('onconnected', [{}]);
        }) // handle when finish
        .fail(function () {
            console.error('Error connecting to signalR');
        }); //handle error*/
        

        makeCid = function () {
            return 'c' + String(stateMap.cid_serial++);
        };

        clearmescommandeDb = function () {
            var mescommande = stateMap.mescommande;
            stateMap.mescommande_db = TAFFY();
            stateMap.mescommande_cid_map = {};
            if (mescommande) {
                stateMap.mescommande_db.insert(mescommande);
                stateMap.mescommande_cid_map[mescommande.cid] = mescommande;
            }
        };

        removemescommande = function (mescommande) {
            if (!mescommande) { return false; }
            // can't remove anonymous person
            if (mescommande.id === configMap.anon_id) {
                return false;
            }
            stateMap.mescommande_db({ cid: mescommande.cid }).remove();
            if (mescommande.cid) {
                delete stateMap.mescommande_cid_map[mescommande.cid];
            }
            return true;
        };

        mescommandeProto = {
            get_is_user: function () {
                return this.cid === stateMap.mescommande.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_mescommande.cid;
            }
        };

        makemescommande = function (mescommande_map) {
            var mescommande,
            cid = mescommande_map.cid,
            id = mescommande_map.id,
            nom = mescommande_map.nom,
            prenom = mescommande_map.prenom,
            genre = mescommande_map.genre,
            jourNaissance = mescommande_map.jourNaissance,
            moisNaissance = mescommande_map.moisNaissance,
            anneeNaissance = mescommande_map.anneeNaissance,
            adresse = mescommande_map.adresse,
            email = mescommande_map.email,
            telephone = mescommande_map.telephone,
            paysId = mescommande_map.paysId,
            paysLib = mescommande_map.paysLib,
            typePieceFournitId = mescommande_map.typePieceFournitId,
            typePieceFournitLib = mescommande_map.typePieceFournitLib,
            numeroPiece = mescommande_map.numeroPiece,
            mescommande_list = mescommande_map.mescommande_list,
            insertDate = mescommande_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'mescommande id and nom required';
            }

            mescommande = Object.create(mescommandeProto);
            mescommande.cid = cid;
            mescommande.nom = nom;
            mescommande.prenom = prenom;
            mescommande.genre = genre;
            mescommande.jourNaissance = jourNaissance;
            mescommande.moisNaissance = moisNaissance;
            mescommande.anneeNaissance = anneeNaissance;
            mescommande.adresse = adresse;
            mescommande.email = email;
            mescommande.telephone = telephone;
            mescommande.paysId = paysId;
            mescommande.paysLib = paysLib,
            mescommande.typePieceFournitId = typePieceFournitId;
            mescommande.typePieceFournitLib = typePieceFournitLib;
            mescommande.numeroPiece = numeroPiece;
            mescommande.mescommande_list = mescommande_list;
            mescommande.insertDate = insertDate;

            if (id) { mescommande.id = id; }

            stateMap.mescommande_cid_map[cid] = mescommande;
            stateMap.mescommande_db.insert(mescommande);
            return mescommande;
        };

        factory.mescommande = (function () {
            var init, get_by_cid, get_db, get_mescommande, _add, _update_list, queryArgs, valide,
                _publish_listchange, _join, _leave, save_cmd, get_by_id, update_cmd;

            queryArgs = {
                pageSize: 50,
                pageNumber: 1
            }

            _update_list = function (arg_list) {
                var i, mescommande_map, make_mescommande_map,
                //mescommande_list = arg_list[0];
                mescommande_list = arg_list;
                clearmescommandeDb();
                mescommande:
                    for (i = 0; i < mescommande_list.length; i++) {
                        mescommande_map = mescommande_list[i];
                        if (!mescommande_map.nom) { continue mescommande; }
                        make_mescommande_map = {
                            cid: makeCid(),
                            id: mescommande_map.id,
                            nom: mescommande_map.nom,
                            prenom: mescommande_map.prenom,
                            genre: mescommande_map.genre,
                            jourNaissance: mescommande_map.jourNaissance,
                            moisNaissance: mescommande_map.moisNaissance,
                            anneeNaissance: mescommande_map.anneeNaissance,
                            adresse: mescommande_map.adresse,
                            email: mescommande_map.email,
                            telephone: mescommande_map.telephone,
                            paysId: mescommande_map.paysId,
                            paysLib: mescommande_map.paysLib,
                            typePieceFournitId: mescommande_map.typePieceFournitId,
                            typePieceFournitLib: mescommande_map.typePieceFournitLib,
                            numeroPiece: mescommande_map.numeroPiece,
                            mescommande_list: mescommande_map.mescommande_list,
                            insertDate: mescommande_map.insertDate
                        };
                        makemescommande(make_mescommande_map);
                    }
                stateMap.mescommande_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-mescommandelistchange', arg_list);
            };

            _add = function (mescommande) {
                return $http.post(serviceBase + 'postmescommande', mescommande).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-mescommande-added', response);
                    return response;
                });
            };

            _edit = function (mescommande) {
                return $http.put(serviceBase + 'putmescommande?id=' + mescommande.id, mescommande).then(function (response) {
                    console.log("update-mescommande");
                    console.log(response);
                    $rootScope.$broadcast('app-update-mescommande', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                //GetAllmescommande(int pageSize, int pageNumber)
                pageSize = arg_map.pageSize;
                pageNumber = arg_map.pageNumber;
                searchText = arg_map.searchText;

                return $http.get(serviceBase + 'mescommande?pageSize=' + pageSize
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
                console.log('# mescommande to save #');
                console.log(cmd_map);

                return $http.post(serviceBase + 'postMescommande', cmd_map)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-insert-mescommande', results);
                    });
            };

            valide = function (cmd_id) {
                console.log('# mescommande to valide #');
                console.log(cmd_id);

                return $http.put(serviceBase + 'valideMescommande?id=' + cmd_id)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-update-mescommande', results);
                    });
            };

            update_cmd = function (mescommande) {
                return $http.put(serviceBase + 'putMescommande?id=' + mescommande.id, mescommande)
                    .then(function (results) {
                        console.log(results);
                        return results;
                        //$rootScope.$broadcast('app-update-mescommande', results);
                    });
            };

            get_by_id = function (cmd_id) {
                console.log('# get mescommande #');
                console.log(cmd_id);

                return $http.get(serviceBase + 'getCommandeById?id=' + cmd_id)
                    .then(function (results) {
                        console.log('#get comm');
                        console.log(results);
                        return results;
                    });
            };

            get_by_cid = function (cid) {
                return stateMap.mescommande_cid_map[cid];
            };
            get_db = function () { return stateMap.mescommande_db; };

            get_mescommande = function (mescommande_id) {
                var mescommandeDB, mescommande;

                mescommandeDB = get_db();
                mescommande = mescommandeDB({ id: mescommande_id }).get();
                console.log(mescommande);
                return mescommande;
            };

            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_mescommande: get_mescommande,
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

        factory.insertMescommandeAnalyse = function (type_contrat_id, analyse_id, employee_id) {

            var type_contrat_id = type_contrat_id;
            var analyse_id = analyse_id;
            var employee_id = employee_id;

            console.log('selected contrat id ' + type_contrat_id);
            console.log('selected analyse id ' + analyse_id);
            return $http.get(serviceBase + 'postMescommandeAnalyse?tcontratid='
                + type_contrat_id + '&analyseid='
                + analyse_id + '&employeeid='
                + employee_id).then(function (results) {
                    var response = results.data.mescommandeAnalyse;
                    $rootScope.$broadcast('app-insert-mescommandeAnalyse', response);
                    return results.data;
                });
        };

        factory.updateAnalyse = function (analyse) {
            return $http.put(serviceBase + 'putAnalyse/' + analyse.id, analyse).then(function (status) {
                $rootScope.$broadcast('app-update-analyse', results.data);
                return status.data;
            });
        };



        factory.insertmescommande = function (mescommande) {
            return $http.post(serviceBase + 'postmescommande', mescommande).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-mescommande', response);
                return response;
            });
        };

        factory.newmescommande = function () {
            return $q.when({ mescommandeId: 0 });
        };



        factory.deletemescommande = function (id) {
            return $http.delete(serviceBase + 'deletemescommande/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getmescommande = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'mescommandeById?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-mescommande', response);
            });
        };

        return factory;
    };

    mescommandeFactory.$inject = injectParams;

    angular.module('app').factory('mescommandeService', mescommandeFactory);

}());