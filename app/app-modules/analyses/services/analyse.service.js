(function () {

    var injectParams = ['$http', '$q', '$rootScope'];

    var analyseFactory = function ($http, $q, $rootScope) {
        var serviceBase = 'api/v1/', analyseList = [],
        factory = {
            pageNumber: 1,
            pageSize : 50
        },

        configMap = { anon_id : 'a0' },

        stateMap = {
            anon_analyse: null,
            cid_serial: 0,
            analyse_cid_map: {},
            analyse_db: TAFFY(),
            analyse : {}
        },

        analyseProto, makeCid, clearanalyseDb, removeanalyse,
        makeanalyse, analyse;

        makeCid = function () {
            return 'c' + String(stateMap.cid_serial++);
        };

        clearanalyseDb = function () {
            var analyse = stateMap.analyse;
            stateMap.analyse_db = TAFFY();
            stateMap.analyse_cid_map = {};
            if (analyse) {
                stateMap.analyse_db.insert(analyse);
                stateMap.analyse_cid_map[analyse.cid] = analyse;
            }
        };

        removeanalyse = function (analyse) {
            if (!analyse) { return false; }
            // can't remove anonymous person
            if (analyse.id === configMap.anon_id) {
                return false;
            }
            stateMap.analyse_db({ cid: analyse.cid }).remove();
            if (analyse.cid) {
                delete stateMap.analyse_cid_map[analyse.cid];
            }
            return true;
        };

        analyseProto = {
            get_is_user: function () {
                return this.cid === stateMap.analyse.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_analyse.cid;
            }
        };

        makeanalyse = function (analyse_map) {
            var analyse,
            cid = analyse_map.cid,
            id = analyse_map.id,
            nom = analyse_map.nom,
            prenom = analyse_map.prenom,
            genre = analyse_map.genre,
            jourNaissance = analyse_map.jourNaissance,
            moisNaissance = analyse_map.moisNaissance,
            anneeNaissance = analyse_map.anneeNaissance,
            adresse = analyse_map.adresse,
            email = analyse_map.email,
            telephone = analyse_map.telephone,
            paysId = analyse_map.paysId,
            paysLib = analyse_map.paysLib,
            typePieceFournitId = analyse_map.typePieceFournitId,
            typePieceFournitLib= analyse_map.typePieceFournitLib,
            numeroPiece = analyse_map.numeroPiece,
            commande_list = analyse_map.commande_list,
            insertDate = analyse_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'analyse id and nom required';
            }

            analyse = Object.create(analyseProto);
            analyse.cid = cid;
            analyse.nom = nom;
            analyse.prenom = prenom;
            analyse.genre = genre;
            analyse.jourNaissance = jourNaissance;
            analyse.moisNaissance = moisNaissance;
            analyse.anneeNaissance = anneeNaissance;
            analyse.adresse = adresse;
            analyse.email = email;
            analyse.telephone = telephone;
            analyse.paysId = paysId;
            analyse.paysLib = paysLib,
            analyse.typePieceFournitId = typePieceFournitId;
            analyse.typePieceFournitLib = typePieceFournitLib;
            analyse.numeroPiece = numeroPiece;
            analyse.commande_list = commande_list;
            analyse.insertDate = insertDate;

            if (id) { analyse.id = id; }

            stateMap.analyse_cid_map[cid] = analyse;
            stateMap.analyse_db.insert(analyse);
            return analyse;
        };

        factory.analyse = (function () {
            var init, get_by_cid, get_db, get_analyse, _add, _update_list, queryArgs,
                _publish_listchange, _join, _leave, save_cmd, get_commande_by_id, update_cmd;

            queryArgs = {
                pageSize : 50,
                pageNumber : 1
            }

            _update_list = function (arg_list) {
                var i, analyse_map, make_analyse_map,
                //analyse_list = arg_list[0];
                analyse_list = arg_list;
                clearanalyseDb();
                analyse:
                    for (i = 0; i < analyse_list.length; i++) {
                        analyse_map = analyse_list[i];
                        if (!analyse_map.nom) { continue analyse; }
                        make_analyse_map = {
                            cid: makeCid(),
                            id: analyse_map.id,
                            nom: analyse_map.nom,
                            prenom: analyse_map.prenom,
                            genre: analyse_map.genre,
                            jourNaissance: analyse_map.jourNaissance,
                            moisNaissance: analyse_map.moisNaissance,
                            anneeNaissance: analyse_map.anneeNaissance,
                            adresse: analyse_map.adresse,
                            email: analyse_map.email,
                            telephone: analyse_map.telephone,
                            paysId: analyse_map.paysId,
                            paysLib: analyse_map.paysLib,
                            typePieceFournitId: analyse_map.typePieceFournitId,
                            typePieceFournitLib: analyse_map.typePieceFournitLib,
                            numeroPiece: analyse_map.numeroPiece,
                            commande_list: analyse_map.commande_list,
                            insertDate: analyse_map.insertDate
                        };
                        makeanalyse(make_analyse_map);
                    }
                stateMap.analyse_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-analyselistchange', arg_list);
            };

            _add = function (analyse) {
                var analyse = analyse;
                return $http.post(serviceBase + 'analyse', analyse).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-analyse-added', response);
                    return response;
                });
            };

            _edit = function (id, analyse) {

                var analyse = analyse;

                return $http.put(serviceBase + 'analyse?id=' + id, analyse).then(function (response) {
                    console.log("update-analyse");
                    console.log(response);
                    $rootScope.$broadcast('app-update-analyse', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                //GetAllanalyse(int pageSize, int pageNumber)
                pageSize = arg_map.pageSize;
                pageNumber = arg_map.pageNumber;
                searchText = arg_map.searchText;

                return $http.get(serviceBase + 'analyses?limit=' + pageSize + '&page=' + pageNumber + '&searchText=' + searchText).then(
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
                //alert('updating...' + acct_map.id);
                return $http.put(serviceBase + 'putAccount?id=' + acct_map.id, acct_map)
                    .then(function (results) {
                        return results;
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
                return stateMap.analyse_cid_map[cid];
            };
            get_db = function () { return stateMap.analyse_db; };

            get_analyse = function (analyse_id) {
                var analyseDB, analyse;

                analyseDB = get_db();
                analyse = analyseDB({ id: analyse_id }).get();
                console.log(analyse);
                return analyse;
            };
            
            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_analyse: get_analyse,
                add: _add,
                edit: _edit,
                save_acct: save_acct,
                update_acct: update_acct,
                join: _join,
                init : init,
                leave : _leave
            };
        }());

        factory.getAccount = function (analyseId) {
            return $http.get(serviceBase + 'empaccount?id=' + analyseId).then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };

        factory.getEditConfig = function () {
            return $http.get(serviceBase + 'analyse/config').then(
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

        factory.insertCommandeAnalyse = function (type_contrat_id, analyse_id, analyse_id) {

            var type_contrat_id = type_contrat_id;
            var analyse_id = analyse_id;
            var analyse_id = analyse_id;
            
            console.log('selected contrat id ' + type_contrat_id);
            console.log('selected analyse id ' + analyse_id);
            return $http.get(serviceBase + 'postCommandeAnalyse?tcontratid='
                + type_contrat_id + '&analyseid='
                + analyse_id + '&analyseid='
                + analyse_id).then(function (results) {
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

        

        factory.insertanalyse = function (analyse) {
            return $http.post(serviceBase + 'postanalyse', analyse).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-analyse', response);
                return response;
            });
        };

        factory.newanalyse = function () {
            return $q.when({ analyseId: 0 });
        };

        

        factory.deleteanalyse = function (id) {
            return $http.delete(serviceBase + 'deleteanalyse/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getanalyse = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'analyse?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-analyse', response);
            });
        };

        return factory;
    };

    analyseFactory.$inject = injectParams;

    angular.module('app').factory('analyseService', analyseFactory);

}());