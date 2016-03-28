(function () {

    var injectParams = ['$http', '$q', '$rootScope'];

    var uniteFactory = function ($http, $q, $rootScope) {
        var serviceBase = 'api/v1/', uniteList = [],
        factory = {
            pageNumber: 1,
            pageSize : 50
        },

        configMap = { anon_id : 'a0' },

        stateMap = {
            anon_unite: null,
            cid_serial: 0,
            unite_cid_map: {},
            unite_db: TAFFY(),
            unite: {}
        },

        uniteProto, makeCid, clearuniteDb, removeunite,
        makeunite, unite;

        makeCid = function () {
            return 'c' + String(stateMap.cid_serial++);
        };

        clearuniteDb = function () {
            var unite = stateMap.unite;
            stateMap.unite_db = TAFFY();
            stateMap.unite_cid_map = {};
            if (unite) {
                stateMap.unite_db.insert(unite);
                stateMap.unite_cid_map[unite.cid] = unite;
            }
        };

        removeunite = function (unite) {
            if (!unite) { return false; }
            // can't remove anonymous person
            if (unite.id === configMap.anon_id) {
                return false;
            }
            stateMap.unite_db({ cid: unite.cid }).remove();
            if (unite.cid) {
                delete stateMap.unite_cid_map[unite.cid];
            }
            return true;
        };

        uniteProto = {
            get_is_user: function () {
                return this.cid === stateMap.unite.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_unite.cid;
            }
        };

        makeunite = function (unite_map) {
            var unite,
            cid = unite_map.cid,
            id = unite_map.id,
            nom = unite_map.nom,
            prenom = unite_map.prenom,
            genre = unite_map.genre,
            jourNaissance = unite_map.jourNaissance,
            moisNaissance = unite_map.moisNaissance,
            anneeNaissance = unite_map.anneeNaissance,
            adresse = unite_map.adresse,
            email = unite_map.email,
            telephone = unite_map.telephone,
            paysId = unite_map.paysId,
            paysLib = unite_map.paysLib,
            typePieceFournitId = unite_map.typePieceFournitId,
            typePieceFournitLib= unite_map.typePieceFournitLib,
            numeroPiece = unite_map.numeroPiece,
            commande_list = unite_map.commande_list,
            insertDate = unite_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'unite id and nom required';
            }

            unite = Object.create(uniteProto);
            unite.cid = cid;
            unite.nom = nom;
            unite.prenom = prenom;
            unite.genre = genre;
            unite.jourNaissance = jourNaissance;
            unite.moisNaissance = moisNaissance;
            unite.anneeNaissance = anneeNaissance;
            unite.adresse = adresse;
            unite.email = email;
            unite.telephone = telephone;
            unite.paysId = paysId;
            unite.paysLib = paysLib,
            unite.typePieceFournitId = typePieceFournitId;
            unite.typePieceFournitLib = typePieceFournitLib;
            unite.numeroPiece = numeroPiece;
            unite.commande_list = commande_list;
            unite.insertDate = insertDate;

            if (id) { unite.id = id; }

            stateMap.unite_cid_map[cid] = unite;
            stateMap.unite_db.insert(unite);
            return unite;
        };

        factory.unite = (function () {
            var init, get_by_cid, get_db, get_unite, _add, _update_list, queryArgs,
                _publish_listchange, _join, _leave, save_cmd, get_commande_by_id, update_cmd;

            queryArgs = {
                pageSize : 50,
                pageNumber : 1
            }

            _update_list = function (arg_list) {
                var i, unite_map, make_unite_map,
                //unite_list = arg_list[0];
                unite_list = arg_list;
                clearuniteDb();
                unite:
                    for (i = 0; i < unite_list.length; i++) {
                        unite_map = unite_list[i];
                        if (!unite_map.nom) { continue unite; }
                        make_unite_map = {
                            cid: makeCid(),
                            id: unite_map.id,
                            nom: unite_map.nom,
                            prenom: unite_map.prenom,
                            genre: unite_map.genre,
                            jourNaissance: unite_map.jourNaissance,
                            moisNaissance: unite_map.moisNaissance,
                            anneeNaissance: unite_map.anneeNaissance,
                            adresse: unite_map.adresse,
                            email: unite_map.email,
                            telephone: unite_map.telephone,
                            paysId: unite_map.paysId,
                            paysLib: unite_map.paysLib,
                            typePieceFournitId: unite_map.typePieceFournitId,
                            typePieceFournitLib: unite_map.typePieceFournitLib,
                            numeroPiece: unite_map.numeroPiece,
                            commande_list: unite_map.commande_list,
                            insertDate: unite_map.insertDate
                        };
                        makeunite(make_unite_map);
                    }
                stateMap.unite_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-unitelistchange', arg_list);
            };

            _add = function (unite) {
                var unite = unite;
                return $http.post(serviceBase + 'unite', unite).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-unite-added', response);
                    return response;
                });
            };

            _edit = function (id, unite) {

                var unite = unite;

                return $http.put(serviceBase + 'unite?id=' + id, unite).then(function (response) {
                    console.log("update-unite");
                    console.log(response);
                    $rootScope.$broadcast('app-update-unite', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                //GetAllunite(int pageSize, int pageNumber)
                pageSize = arg_map.pageSize;
                pageNumber = arg_map.pageNumber;
                searchText = arg_map.searchText;

                return $http.get(serviceBase + 'unites?limit=' + pageSize + '&page=' + pageNumber + '&searchText=' + searchText).then(
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
                return stateMap.unite_cid_map[cid];
            };
            get_db = function () { return stateMap.unite_db; };

            get_unite = function (unite_id) {
                var uniteDB, unite;

                uniteDB = get_db();
                unite = uniteDB({ id: unite_id }).get();
                console.log(unite);
                return unite;
            };
            
            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_unite: get_unite,
                add: _add,
                edit: _edit,
                save_acct: save_acct,
                update_acct: update_acct,
                join: _join,
                init : init,
                leave : _leave
            };
        }());

        factory.getAccount = function (uniteId) {
            return $http.get(serviceBase + 'empaccount?id=' + uniteId).then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };

        factory.getEditConfig = function () {
            return $http.get(serviceBase + 'unite/config').then(
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

        factory.insertCommandeAnalyse = function (type_contrat_id, analyse_id, unite_id) {

            var type_contrat_id = type_contrat_id;
            var analyse_id = analyse_id;
            var unite_id = unite_id;
            
            console.log('selected contrat id ' + type_contrat_id);
            console.log('selected analyse id ' + analyse_id);
            return $http.get(serviceBase + 'postCommandeAnalyse?tcontratid='
                + type_contrat_id + '&analyseid='
                + analyse_id + '&uniteid='
                + unite_id).then(function (results) {
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

        

        factory.insertunite = function (unite) {
            return $http.post(serviceBase + 'unite', unite).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-unite', response);
                return response;
            });
        };

        factory.newunite = function () {
            return $q.when({ uniteId: 0 });
        };

        

        factory.deleteunite = function (id) {
            return $http.delete(serviceBase + 'unite/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getunite = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'uniteById?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-unite', response);
            });
        };

        return factory;
    };

    uniteFactory.$inject = injectParams;

    angular.module('app').factory('uniteService', uniteFactory);

}());