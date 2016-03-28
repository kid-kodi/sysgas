(function () {

    var injectParams = ['$http', '$q', '$rootScope'];

    var departementFactory = function ($http, $q, $rootScope) {
        var serviceBase = 'api/v1/', departementList = [],
        factory = {
            pageNumber: 1,
            pageSize : 50
        },

        configMap = { anon_id : 'a0' },

        stateMap = {
            anon_departement: null,
            cid_serial: 0,
            departement_cid_map: {},
            departement_db: TAFFY(),
            departement: {}
        },

        departementProto, makeCid, cleardepartementDb, removedepartement,
        makedepartement, departement;

        makeCid = function () {
            return 'c' + String(stateMap.cid_serial++);
        };

        cleardepartementDb = function () {
            var departement = stateMap.departement;
            stateMap.departement_db = TAFFY();
            stateMap.departement_cid_map = {};
            if (departement) {
                stateMap.departement_db.insert(departement);
                stateMap.departement_cid_map[departement.cid] = departement;
            }
        };

        removedepartement = function (departement) {
            if (!departement) { return false; }
            // can't remove anonymous person
            if (departement.id === configMap.anon_id) {
                return false;
            }
            stateMap.departement_db({ cid: departement.cid }).remove();
            if (departement.cid) {
                delete stateMap.departement_cid_map[departement.cid];
            }
            return true;
        };

        departementProto = {
            get_is_user: function () {
                return this.cid === stateMap.departement.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_departement.cid;
            }
        };

        makedepartement = function (departement_map) {
            var departement,
            cid = departement_map.cid,
            id = departement_map.id,
            nom = departement_map.nom,
            prenom = departement_map.prenom,
            genre = departement_map.genre,
            jourNaissance = departement_map.jourNaissance,
            moisNaissance = departement_map.moisNaissance,
            anneeNaissance = departement_map.anneeNaissance,
            adresse = departement_map.adresse,
            email = departement_map.email,
            telephone = departement_map.telephone,
            paysId = departement_map.paysId,
            paysLib = departement_map.paysLib,
            typePieceFournitId = departement_map.typePieceFournitId,
            typePieceFournitLib= departement_map.typePieceFournitLib,
            numeroPiece = departement_map.numeroPiece,
            commande_list = departement_map.commande_list,
            insertDate = departement_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'departement id and nom required';
            }

            departement = Object.create(departementProto);
            departement.cid = cid;
            departement.nom = nom;
            departement.prenom = prenom;
            departement.genre = genre;
            departement.jourNaissance = jourNaissance;
            departement.moisNaissance = moisNaissance;
            departement.anneeNaissance = anneeNaissance;
            departement.adresse = adresse;
            departement.email = email;
            departement.telephone = telephone;
            departement.paysId = paysId;
            departement.paysLib = paysLib,
            departement.typePieceFournitId = typePieceFournitId;
            departement.typePieceFournitLib = typePieceFournitLib;
            departement.numeroPiece = numeroPiece;
            departement.commande_list = commande_list;
            departement.insertDate = insertDate;

            if (id) { departement.id = id; }

            stateMap.departement_cid_map[cid] = departement;
            stateMap.departement_db.insert(departement);
            return departement;
        };

        factory.departement = (function () {
            var init, get_by_cid, get_db, get_departement, _add, _update_list, queryArgs,
                _publish_listchange, _join, _leave, save_cmd, get_commande_by_id, update_cmd;

            queryArgs = {
                pageSize : 50,
                pageNumber : 1
            }

            _update_list = function (arg_list) {
                var i, departement_map, make_departement_map,
                //departement_list = arg_list[0];
                departement_list = arg_list;
                cleardepartementDb();
                departement:
                    for (i = 0; i < departement_list.length; i++) {
                        departement_map = departement_list[i];
                        if (!departement_map.nom) { continue departement; }
                        make_departement_map = {
                            cid: makeCid(),
                            id: departement_map.id,
                            nom: departement_map.nom,
                            prenom: departement_map.prenom,
                            genre: departement_map.genre,
                            jourNaissance: departement_map.jourNaissance,
                            moisNaissance: departement_map.moisNaissance,
                            anneeNaissance: departement_map.anneeNaissance,
                            adresse: departement_map.adresse,
                            email: departement_map.email,
                            telephone: departement_map.telephone,
                            paysId: departement_map.paysId,
                            paysLib: departement_map.paysLib,
                            typePieceFournitId: departement_map.typePieceFournitId,
                            typePieceFournitLib: departement_map.typePieceFournitLib,
                            numeroPiece: departement_map.numeroPiece,
                            commande_list: departement_map.commande_list,
                            insertDate: departement_map.insertDate
                        };
                        makedepartement(make_departement_map);
                    }
                stateMap.departement_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-departementlistchange', arg_list);
            };

            _add = function (departementLib, reference) {
                var departement = {
                    departementLib: departementLib,
                    reference      : reference
                };
                return $http.post(serviceBase + 'departement', departement).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-departement-added', response);
                    return response;
                });
            };

            _edit = function (id, departementLib, reference) {

                var departement = {
                    id: id,
                    departementLib: departementLib,
                    reference      : reference,
                };

                return $http.put(serviceBase + 'departement?id=' + departement.id, departement).then(function (response) {
                    console.log("update-departement");
                    console.log(response);
                    $rootScope.$broadcast('app-update-departement', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                //GetAlldepartement(int pageSize, int pageNumber)
                pageSize = arg_map.pageSize;
                pageNumber = arg_map.pageNumber;
                searchText = arg_map.searchText;

                return $http.get(serviceBase + 'departements?limit=' + pageSize + '&page=' + pageNumber + '&searchText=' + searchText).then(
                function (results) {
                    console.log(results);

                    var arg_list = results.data;
                    _update_list(arg_list);
                    return arg_list;
                });
            };

            _leave = function () { };

            save_acct = function (acct_map) {
                console.log('# commande to save #');
                console.log(acct_map);
                
                return $http.post(serviceBase + 'postAccount', acct_map)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-insert-commande', results);
                    });
            };

            update_acct = function (acct_map) {
                alert('updating...' + acct_map.id);
                return $http.put(serviceBase + 'putAccount?id=' + acct_map.id, acct_map)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-update-commande', results);
                    });
            };

            get_commande_by_id = function (cmd_id) {
                console.log('# get commande #');
                console.log(cmd_id);

                return $http.get(serviceBase + 'getCommandeById?id=' + cmd_id)
                    .then(function (results) {
                        console.log('#get comm');
                        console.log(results);
                        $rootScope.$broadcast('app-set-commande', results);
                    });
            };

            get_by_cid = function (cid) {
                return stateMap.departement_cid_map[cid];
            };
            get_db = function () { return stateMap.departement_db; };

            get_departement = function (departement_id) {
                var departementDB, departement;

                departementDB = get_db();
                departement = departementDB({ id: departement_id }).get();
                console.log(departement);
                return departement;
            };
            
            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_departement: get_departement,
                add: _add,
                edit: _edit,
                save_acct: save_acct,
                update_acct: update_acct,
                join: _join,
                init : init,
                leave : _leave
            };
        }());

        factory.getAccount = function (departementId) {
            return $http.get(serviceBase + 'empaccount?id=' + departementId).then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };

        factory.getEditConfig = function () {
            return $http.get(serviceBase + 'departement/editconfig').then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };

        factory.getAcctConfig = function () {
            return $http.get(serviceBase + 'acctconfig').then(
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

        factory.insertCommandeAnalyse = function (type_contrat_id, analyse_id, departement_id) {

            var type_contrat_id = type_contrat_id;
            var analyse_id = analyse_id;
            var departement_id = departement_id;
            
            console.log('selected contrat id ' + type_contrat_id);
            console.log('selected analyse id ' + analyse_id);
            return $http.get(serviceBase + 'postCommandeAnalyse?tcontratid='
                + type_contrat_id + '&analyseid='
                + analyse_id + '&departementid='
                + departement_id).then(function (results) {
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

        

        factory.insertdepartement = function (departement) {
            return $http.post(serviceBase + 'postdepartement', departement).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-departement', response);
                return response;
            });
        };

        factory.newdepartement = function () {
            return $q.when({ departementId: 0 });
        };

        

        factory.deletedepartement = function (id) {
            return $http.delete(serviceBase + 'deletedepartement/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getdepartement = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'departement?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-departement', response);
            });
        };

        return factory;
    };

    departementFactory.$inject = injectParams;

    angular.module('app').factory('departementService', departementFactory);

}());