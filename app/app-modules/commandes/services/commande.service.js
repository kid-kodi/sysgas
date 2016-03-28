(function () {
    
    var injectParams = ['$http', '$q', '$rootScope'];

    var commandeFactory = function ($http, $q, $rootScope) {
        var serviceBase = 'api/v1/', commandeList = [],
        factory = {
            cmd_range: 1,
            pageNumber: 1,
            pageSize: 50
        },

        configMap = { anon_id: 'a0' },

        stateMap = {
            anon_commande: null,
            cid_serial: 0,
            commande_cid_map: {},
            commande_db: TAFFY(),
            commande: {}
        },

        commandeProto, makeCid, clearcommandeDb, removecommande,
        makecommande, commande;

        

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

        clearcommandeDb = function () {
            var commande = stateMap.commande;
            stateMap.commande_db = TAFFY();
            stateMap.commande_cid_map = {};
            if (commande) {
                stateMap.commande_db.insert(commande);
                stateMap.commande_cid_map[commande.cid] = commande;
            }
        };

        removecommande = function (commande) {
            if (!commande) { return false; }
            // can't remove anonymous person
            if (commande.id === configMap.anon_id) {
                return false;
            }
            stateMap.commande_db({ cid: commande.cid }).remove();
            if (commande.cid) {
                delete stateMap.commande_cid_map[commande.cid];
            }
            return true;
        };

        commandeProto = {
            get_is_user: function () {
                return this.cid === stateMap.commande.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_commande.cid;
            }
        };

        makecommande = function (commande_map) {
            var commande,
            cid = commande_map.cid,
            id = commande_map.id,
            nom = commande_map.nom,
            prenom = commande_map.prenom,
            genre = commande_map.genre,
            jourNaissance = commande_map.jourNaissance,
            moisNaissance = commande_map.moisNaissance,
            anneeNaissance = commande_map.anneeNaissance,
            adresse = commande_map.adresse,
            email = commande_map.email,
            telephone = commande_map.telephone,
            paysId = commande_map.paysId,
            paysLib = commande_map.paysLib,
            typePieceFournitId = commande_map.typePieceFournitId,
            typePieceFournitLib = commande_map.typePieceFournitLib,
            numeroPiece = commande_map.numeroPiece,
            commande_list = commande_map.commande_list,
            insertDate = commande_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'commande id and nom required';
            }

            commande = Object.create(commandeProto);
            commande.cid = cid;
            commande.nom = nom;
            commande.prenom = prenom;
            commande.genre = genre;
            commande.jourNaissance = jourNaissance;
            commande.moisNaissance = moisNaissance;
            commande.anneeNaissance = anneeNaissance;
            commande.adresse = adresse;
            commande.email = email;
            commande.telephone = telephone;
            commande.paysId = paysId;
            commande.paysLib = paysLib,
            commande.typePieceFournitId = typePieceFournitId;
            commande.typePieceFournitLib = typePieceFournitLib;
            commande.numeroPiece = numeroPiece;
            commande.commande_list = commande_list;
            commande.insertDate = insertDate;

            if (id) { commande.id = id; }

            stateMap.commande_cid_map[cid] = commande;
            stateMap.commande_db.insert(commande);
            return commande;
        };

        factory.commande = (function () {
            var init, get_by_cid, get_db, get_commande, _add, _update_list, queryArgs, valide,
                _publish_listchange, _join, _leave, save_cmd, get_by_id, update_cmd;

            queryArgs = {
                pageSize: 50,
                pageNumber: 1
            }

            _update_list = function (arg_list) {
                var i, commande_map, make_commande_map,
                //commande_list = arg_list[0];
                commande_list = arg_list;
                clearcommandeDb();
                commande:
                    for (i = 0; i < commande_list.length; i++) {
                        commande_map = commande_list[i];
                        if (!commande_map.nom) { continue commande; }
                        make_commande_map = {
                            cid: makeCid(),
                            id: commande_map.id,
                            nom: commande_map.nom,
                            prenom: commande_map.prenom,
                            genre: commande_map.genre,
                            jourNaissance: commande_map.jourNaissance,
                            moisNaissance: commande_map.moisNaissance,
                            anneeNaissance: commande_map.anneeNaissance,
                            adresse: commande_map.adresse,
                            email: commande_map.email,
                            telephone: commande_map.telephone,
                            paysId: commande_map.paysId,
                            paysLib: commande_map.paysLib,
                            typePieceFournitId: commande_map.typePieceFournitId,
                            typePieceFournitLib: commande_map.typePieceFournitLib,
                            numeroPiece: commande_map.numeroPiece,
                            commande_list: commande_map.commande_list,
                            insertDate: commande_map.insertDate
                        };
                        makecommande(make_commande_map);
                    }
                stateMap.commande_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-commandelistchange', arg_list);
            };

            _add = function (commande) {
                return $http.post(serviceBase + 'postcommande', commande).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-commande-added', response);
                    return response;
                });
            };

            _edit = function (commande) {
                return $http.put(serviceBase + 'putcommande?id=' + commande.id, commande).then(function (response) {
                    console.log("update-commande");
                    console.log(response);
                    $rootScope.$broadcast('app-update-commande', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                var
                cmd_range = arg_map.cmd_range,
                pageSize = arg_map.pageSize,
                pageNumber = arg_map.pageNumber,
                searchText = arg_map.searchText;

                return $http.get(serviceBase + 'commande?filter=' + cmd_range
                    + '&limit=' + pageSize
                    + '&page=' + pageNumber
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
                console.log('# commande to save #');
                console.log(cmd_map);

                return $http.post(serviceBase + 'postCommande', cmd_map)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-insert-commande', results);
                    });
            };

            valide = function (cmd_id) {
                console.log('# commande to valide #');
                console.log(cmd_id);

                return $http.put(serviceBase + 'valideCommande?id=' + cmd_id)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-update-commande', results);
                    });
            };

            update_cmd = function (commande) {
                return $http.put(serviceBase + 'putCommande?id=' + commande.id, commande)
                    .then(function (results) {
                        console.log(results);
                        return results;
                        //$rootScope.$broadcast('app-update-commande', results);
                    });
            };

            get_by_id = function (cmd_id) {
                console.log('# get commande #');
                console.log(cmd_id);

                return $http.get(serviceBase + 'getCommandeById?id=' + cmd_id)
                    .then(function (results) {
                        console.log('#get comm');
                        console.log(results);
                        return results;
                    });
            };

            get_by_cid = function (cid) {
                return stateMap.commande_cid_map[cid];
            };
            get_db = function () { return stateMap.commande_db; };

            get_commande = function (commande_id) {
                var commandeDB, commande;

                commandeDB = get_db();
                commande = commandeDB({ id: commande_id }).get();
                console.log(commande);
                return commande;
            };

            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_commande: get_commande,
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
                    var response = results.data.commandeAnalyse;
                    $rootScope.$broadcast('app-insert-commandeAnalyse', response);
                    return results.data;
                });
        };

        factory.updateAnalyse = function (analyse) {
            return $http.put(serviceBase + 'putAnalyse/' + analyse.id, analyse).then(function (status) {
                $rootScope.$broadcast('app-update-analyse', results.data);
                return status.data;
            });
        };



        factory.insertcommande = function (commande) {
            return $http.post(serviceBase + 'postcommande', commande).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-commande', response);
                return response;
            });
        };

        factory.newcommande = function () {
            return $q.when({ commandeId: 0 });
        };



        factory.deletecommande = function (id) {
            return $http.delete(serviceBase + 'deletecommande/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getcommande = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'commandeById?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-commande', response);
            });
        };

        return factory;
    };

    commandeFactory.$inject = injectParams;

    angular.module('app').factory('commandeService', commandeFactory);

}());