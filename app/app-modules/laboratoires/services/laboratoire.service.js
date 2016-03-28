(function () {

    var injectParams = ['$http', '$q', '$rootScope'];

    var laboratoireFactory = function ($http, $q, $rootScope) {
        var serviceBase = 'api/v1/', laboratoireList = [],
        factory = {
            pageNumber: 1,
            pageSize : 50
        },

        configMap = { anon_id : 'a0' },

        stateMap = {
            anon_laboratoire: null,
            cid_serial: 0,
            laboratoire_cid_map: {},
            laboratoire_db: TAFFY(),
            laboratoire: {}
        },

        laboratoireProto, makeCid, clearlaboratoireDb, removelaboratoire,
        makelaboratoire, laboratoire;

        makeCid = function () {
            return 'c' + String(stateMap.cid_serial++);
        };

        clearlaboratoireDb = function () {
            var laboratoire = stateMap.laboratoire;
            stateMap.laboratoire_db = TAFFY();
            stateMap.laboratoire_cid_map = {};
            if (laboratoire) {
                stateMap.laboratoire_db.insert(laboratoire);
                stateMap.laboratoire_cid_map[laboratoire.cid] = laboratoire;
            }
        };

        removelaboratoire = function (laboratoire) {
            if (!laboratoire) { return false; }
            // can't remove anonymous person
            if (laboratoire.id === configMap.anon_id) {
                return false;
            }
            stateMap.laboratoire_db({ cid: laboratoire.cid }).remove();
            if (laboratoire.cid) {
                delete stateMap.laboratoire_cid_map[laboratoire.cid];
            }
            return true;
        };

        laboratoireProto = {
            get_is_user: function () {
                return this.cid === stateMap.laboratoire.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_laboratoire.cid;
            }
        };

        makelaboratoire = function (laboratoire_map) {
            var laboratoire,
            cid = laboratoire_map.cid,
            id = laboratoire_map.id,
            nom = laboratoire_map.nom,
            prenom = laboratoire_map.prenom,
            genre = laboratoire_map.genre,
            jourNaissance = laboratoire_map.jourNaissance,
            moisNaissance = laboratoire_map.moisNaissance,
            anneeNaissance = laboratoire_map.anneeNaissance,
            adresse = laboratoire_map.adresse,
            email = laboratoire_map.email,
            telephone = laboratoire_map.telephone,
            paysId = laboratoire_map.paysId,
            paysLib = laboratoire_map.paysLib,
            typePieceFournitId = laboratoire_map.typePieceFournitId,
            typePieceFournitLib= laboratoire_map.typePieceFournitLib,
            numeroPiece = laboratoire_map.numeroPiece,
            commande_list = laboratoire_map.commande_list,
            insertDate = laboratoire_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'laboratoire id and nom required';
            }

            laboratoire = Object.create(laboratoireProto);
            laboratoire.cid = cid;
            laboratoire.nom = nom;
            laboratoire.prenom = prenom;
            laboratoire.genre = genre;
            laboratoire.jourNaissance = jourNaissance;
            laboratoire.moisNaissance = moisNaissance;
            laboratoire.anneeNaissance = anneeNaissance;
            laboratoire.adresse = adresse;
            laboratoire.email = email;
            laboratoire.telephone = telephone;
            laboratoire.paysId = paysId;
            laboratoire.paysLib = paysLib,
            laboratoire.typePieceFournitId = typePieceFournitId;
            laboratoire.typePieceFournitLib = typePieceFournitLib;
            laboratoire.numeroPiece = numeroPiece;
            laboratoire.commande_list = commande_list;
            laboratoire.insertDate = insertDate;

            if (id) { laboratoire.id = id; }

            stateMap.laboratoire_cid_map[cid] = laboratoire;
            stateMap.laboratoire_db.insert(laboratoire);
            return laboratoire;
        };

        factory.laboratoire = (function () {
            var init, get_by_cid, get_db, get_laboratoire, _add, _update_list, queryArgs,
                _publish_listchange, _join, _leave, save_cmd, get_commande_by_id, update_cmd;

            queryArgs = {
                pageSize : 50,
                pageNumber : 1
            }

            _update_list = function (arg_list) {
                var i, laboratoire_map, make_laboratoire_map,
                //laboratoire_list = arg_list[0];
                laboratoire_list = arg_list;
                clearlaboratoireDb();
                laboratoire:
                    for (i = 0; i < laboratoire_list.length; i++) {
                        laboratoire_map = laboratoire_list[i];
                        if (!laboratoire_map.nom) { continue laboratoire; }
                        make_laboratoire_map = {
                            cid: makeCid(),
                            id: laboratoire_map.id,
                            nom: laboratoire_map.nom,
                            prenom: laboratoire_map.prenom,
                            genre: laboratoire_map.genre,
                            jourNaissance: laboratoire_map.jourNaissance,
                            moisNaissance: laboratoire_map.moisNaissance,
                            anneeNaissance: laboratoire_map.anneeNaissance,
                            adresse: laboratoire_map.adresse,
                            email: laboratoire_map.email,
                            telephone: laboratoire_map.telephone,
                            paysId: laboratoire_map.paysId,
                            paysLib: laboratoire_map.paysLib,
                            typePieceFournitId: laboratoire_map.typePieceFournitId,
                            typePieceFournitLib: laboratoire_map.typePieceFournitLib,
                            numeroPiece: laboratoire_map.numeroPiece,
                            commande_list: laboratoire_map.commande_list,
                            insertDate: laboratoire_map.insertDate
                        };
                        makelaboratoire(make_laboratoire_map);
                    }
                stateMap.laboratoire_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-laboratoirelistchange', arg_list);
            };

            _add = function (laboratoire) {
                var laboratoire = laboratoire;
                return $http.post(serviceBase + 'laboratoire', laboratoire).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-laboratoire-added', response);
                    return response;
                });
            };

            _edit = function (id, laboratoire) {

                var laboratoire = laboratoire;

                return $http.put(serviceBase + 'laboratoire?id=' + id, laboratoire).then(function (response) {
                    console.log("update-laboratoire");
                    console.log(response);
                    $rootScope.$broadcast('app-update-laboratoire', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                //GetAlllaboratoire(int pageSize, int pageNumber)
                pageSize = arg_map.pageSize;
                pageNumber = arg_map.pageNumber;
                searchText = arg_map.searchText;

                return $http.get(serviceBase + 'laboratoires?limit=' + pageSize + '&page=' + pageNumber + '&searchText=' + searchText).then(
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
                return stateMap.laboratoire_cid_map[cid];
            };
            get_db = function () { return stateMap.laboratoire_db; };

            get_laboratoire = function (laboratoire_id) {
                var laboratoireDB, laboratoire;

                laboratoireDB = get_db();
                laboratoire = laboratoireDB({ id: laboratoire_id }).get();
                console.log(laboratoire);
                return laboratoire;
            };
            
            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_laboratoire: get_laboratoire,
                add: _add,
                edit: _edit,
                save_acct: save_acct,
                update_acct: update_acct,
                join: _join,
                init : init,
                leave : _leave
            };
        }());

        factory.getAccount = function (laboratoireId) {
            return $http.get(serviceBase + 'empaccount?id=' + laboratoireId).then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };

        factory.getEditConfig = function () {
            return $http.get(serviceBase + 'laboratoire/config').then(
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

        factory.insertCommandeAnalyse = function (type_contrat_id, analyse_id, laboratoire_id) {

            var type_contrat_id = type_contrat_id;
            var analyse_id = analyse_id;
            var laboratoire_id = laboratoire_id;
            
            console.log('selected contrat id ' + type_contrat_id);
            console.log('selected analyse id ' + analyse_id);
            return $http.get(serviceBase + 'postCommandeAnalyse?tcontratid='
                + type_contrat_id + '&analyseid='
                + analyse_id + '&laboratoireid='
                + laboratoire_id).then(function (results) {
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

        

        factory.insertlaboratoire = function (laboratoire) {
            return $http.post(serviceBase + 'postlaboratoire', laboratoire).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-laboratoire', response);
                return response;
            });
        };

        factory.newlaboratoire = function () {
            return $q.when({ laboratoireId: 0 });
        };

        

        factory.deletelaboratoire = function (id) {
            return $http.delete(serviceBase + 'deletelaboratoire/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getlaboratoire = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'laboratoire?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-laboratoire', response);
            });
        };

        return factory;
    };

    laboratoireFactory.$inject = injectParams;

    angular.module('app').factory('laboratoireService', laboratoireFactory);

}());