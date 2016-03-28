(function () {

    var injectParams = ['$http', '$q', '$rootScope'];

    var societeFactory = function ($http, $q, $rootScope) {
        var serviceBase = 'api/v1/', societeList = [],
        factory = {
            pageNumber: 1,
            pageSize : 50
        },

        configMap = { anon_id : 'a0' },

        stateMap = {
            anon_societe: null,
            cid_serial: 0,
            societe_cid_map: {},
            societe_db: TAFFY(),
            societe: {}
        },

        societeProto, makeCid, clearsocieteDb, removesociete,
        makesociete, societe;

        makeCid = function () {
            return 'c' + String(stateMap.cid_serial++);
        };

        clearsocieteDb = function () {
            var societe = stateMap.societe;
            stateMap.societe_db = TAFFY();
            stateMap.societe_cid_map = {};
            if (societe) {
                stateMap.societe_db.insert(societe);
                stateMap.societe_cid_map[societe.cid] = societe;
            }
        };

        removesociete = function (societe) {
            if (!societe) { return false; }
            // can't remove anonymous person
            if (societe.id === configMap.anon_id) {
                return false;
            }
            stateMap.societe_db({ cid: societe.cid }).remove();
            if (societe.cid) {
                delete stateMap.societe_cid_map[societe.cid];
            }
            return true;
        };

        societeProto = {
            get_is_user: function () {
                return this.cid === stateMap.societe.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_societe.cid;
            }
        };

        makesociete = function (societe_map) {
            var societe,
            cid = societe_map.cid,
            id = societe_map.id,
            nom = societe_map.nom,
            prenom = societe_map.prenom,
            genre = societe_map.genre,
            jourNaissance = societe_map.jourNaissance,
            moisNaissance = societe_map.moisNaissance,
            anneeNaissance = societe_map.anneeNaissance,
            adresse = societe_map.adresse,
            email = societe_map.email,
            telephone = societe_map.telephone,
            paysId = societe_map.paysId,
            paysLib = societe_map.paysLib,
            typePieceFournitId = societe_map.typePieceFournitId,
            typePieceFournitLib= societe_map.typePieceFournitLib,
            numeroPiece = societe_map.numeroPiece,
            commande_list = societe_map.commande_list,
            insertDate = societe_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'societe id and nom required';
            }

            societe = Object.create(societeProto);
            societe.cid = cid;
            societe.nom = nom;
            societe.prenom = prenom;
            societe.genre = genre;
            societe.jourNaissance = jourNaissance;
            societe.moisNaissance = moisNaissance;
            societe.anneeNaissance = anneeNaissance;
            societe.adresse = adresse;
            societe.email = email;
            societe.telephone = telephone;
            societe.paysId = paysId;
            societe.paysLib = paysLib,
            societe.typePieceFournitId = typePieceFournitId;
            societe.typePieceFournitLib = typePieceFournitLib;
            societe.numeroPiece = numeroPiece;
            societe.commande_list = commande_list;
            societe.insertDate = insertDate;

            if (id) { societe.id = id; }

            stateMap.societe_cid_map[cid] = societe;
            stateMap.societe_db.insert(societe);
            return societe;
        };

        factory.societe = (function () {
            var init, get_by_cid, get_db, get_societe, _add, _update_list, queryArgs,
                _publish_listchange, _join, _leave, save_cmd, get_commande_by_id, update_cmd;

            queryArgs = {
                pageSize : 50,
                pageNumber : 1
            }

            _update_list = function (arg_list) {
                var i, societe_map, make_societe_map,
                //societe_list = arg_list[0];
                societe_list = arg_list;
                clearsocieteDb();
                societe:
                    for (i = 0; i < societe_list.length; i++) {
                        societe_map = societe_list[i];
                        if (!societe_map.nom) { continue societe; }
                        make_societe_map = {
                            cid: makeCid(),
                            id: societe_map.id,
                            nom: societe_map.nom,
                            prenom: societe_map.prenom,
                            genre: societe_map.genre,
                            jourNaissance: societe_map.jourNaissance,
                            moisNaissance: societe_map.moisNaissance,
                            anneeNaissance: societe_map.anneeNaissance,
                            adresse: societe_map.adresse,
                            email: societe_map.email,
                            telephone: societe_map.telephone,
                            paysId: societe_map.paysId,
                            paysLib: societe_map.paysLib,
                            typePieceFournitId: societe_map.typePieceFournitId,
                            typePieceFournitLib: societe_map.typePieceFournitLib,
                            numeroPiece: societe_map.numeroPiece,
                            commande_list: societe_map.commande_list,
                            insertDate: societe_map.insertDate
                        };
                        makesociete(make_societe_map);
                    }
                stateMap.societe_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-societelistchange', arg_list);
            };

            _add = function (societeLib, selection) {
                var societe = {
                    societeLib: societeLib,
                    SelectedTypeContratId: selection
                };
                return $http.post(serviceBase + 'societe', societe).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-societe-added', response);
                    return response;
                });
            };

            _edit = function (id, societeName, selection) {

                var societe = {
                    id: id,
                    societeLib: societeName,
                    SelectedTypeContratId: selection
                };

                return $http.put(serviceBase + 'societe?id=' + societe.id, societe).then(function (response) {
                    console.log("update-societe");
                    console.log(response);
                    $rootScope.$broadcast('app-update-societe', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                //GetAllsociete(int pageSize, int pageNumber)
                pageSize = arg_map.pageSize;
                pageNumber = arg_map.pageNumber;
                searchText = arg_map.searchText;

                return $http.get(serviceBase + 'societe?limit=' + pageSize + '&page=' + pageNumber + '&searchText=' + searchText).then(
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
                return stateMap.societe_cid_map[cid];
            };
            get_db = function () { return stateMap.societe_db; };

            get_societe = function (societe_id) {
                var societeDB, societe;

                societeDB = get_db();
                societe = societeDB({ id: societe_id }).get();
                console.log(societe);
                return societe;
            };
            
            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_societe: get_societe,
                add: _add,
                edit: _edit,
                save_acct: save_acct,
                update_acct: update_acct,
                join: _join,
                init : init,
                leave : _leave
            };
        }());

        factory.getAccount = function (societeId) {
            return $http.get(serviceBase + 'empaccount?id=' + societeId).then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };

        factory.getEditConfig = function () {
            return $http.get(serviceBase + 'societe/editconfig').then(
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

        factory.insertCommandeAnalyse = function (type_contrat_id, analyse_id, societe_id) {

            var type_contrat_id = type_contrat_id;
            var analyse_id = analyse_id;
            var societe_id = societe_id;
            
            console.log('selected contrat id ' + type_contrat_id);
            console.log('selected analyse id ' + analyse_id);
            return $http.get(serviceBase + 'postCommandeAnalyse?tcontratid='
                + type_contrat_id + '&analyseid='
                + analyse_id + '&societeid='
                + societe_id).then(function (results) {
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

        

        factory.insertsociete = function (societe) {
            return $http.post(serviceBase + 'postsociete', societe).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-societe', response);
                return response;
            });
        };

        factory.newsociete = function () {
            return $q.when({ societeId: 0 });
        };

        

        factory.deletesociete = function (id) {
            return $http.delete(serviceBase + 'deletesociete/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getsociete = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'societeById?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-societe', response);
            });
        };

        return factory;
    };

    societeFactory.$inject = injectParams;

    angular.module('app').factory('societeService', societeFactory);

}());