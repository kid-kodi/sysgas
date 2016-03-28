(function () {

    var injectParams = ['$http', '$q', '$rootScope'];

    var factureFactory = function ($http, $q, $rootScope) {
        var serviceBase = 'api/v1/', factureList = [],
        factory = {
            pageNumber: 1,
            pageSize : 50
        },

        configMap = { anon_id : 'a0' },

        stateMap = {
            anon_facture: null,
            cid_serial: 0,
            facture_cid_map: {},
            facture_db: TAFFY(),
            facture: {}
        },

        factureProto, makeCid, clearfactureDb, removefacture,
        makefacture, facture;

        makeCid = function () {
            return 'c' + String(stateMap.cid_serial++);
        };

        clearfactureDb = function () {
            var facture = stateMap.facture;
            stateMap.facture_db = TAFFY();
            stateMap.facture_cid_map = {};
            if (facture) {
                stateMap.facture_db.insert(facture);
                stateMap.facture_cid_map[facture.cid] = facture;
            }
        };

        removefacture = function (facture) {
            if (!facture) { return false; }
            // can't remove anonymous person
            if (facture.id === configMap.anon_id) {
                return false;
            }
            stateMap.facture_db({ cid: facture.cid }).remove();
            if (facture.cid) {
                delete stateMap.facture_cid_map[facture.cid];
            }
            return true;
        };

        factureProto = {
            get_is_user: function () {
                return this.cid === stateMap.facture.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_facture.cid;
            }
        };

        makefacture = function (facture_map) {
            var facture,
            cid = facture_map.cid,
            id = facture_map.id,
            nom = facture_map.nom,
            prenom = facture_map.prenom,
            genre = facture_map.genre,
            jourNaissance = facture_map.jourNaissance,
            moisNaissance = facture_map.moisNaissance,
            anneeNaissance = facture_map.anneeNaissance,
            adresse = facture_map.adresse,
            email = facture_map.email,
            telephone = facture_map.telephone,
            paysId = facture_map.paysId,
            paysLib = facture_map.paysLib,
            typePieceFournitId = facture_map.typePieceFournitId,
            typePieceFournitLib= facture_map.typePieceFournitLib,
            numeroPiece = facture_map.numeroPiece,
            commande_list = facture_map.commande_list,
            insertDate = facture_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'facture id and nom required';
            }

            facture = Object.create(factureProto);
            facture.cid = cid;
            facture.nom = nom;
            facture.prenom = prenom;
            facture.genre = genre;
            facture.jourNaissance = jourNaissance;
            facture.moisNaissance = moisNaissance;
            facture.anneeNaissance = anneeNaissance;
            facture.adresse = adresse;
            facture.email = email;
            facture.telephone = telephone;
            facture.paysId = paysId;
            facture.paysLib = paysLib,
            facture.typePieceFournitId = typePieceFournitId;
            facture.typePieceFournitLib = typePieceFournitLib;
            facture.numeroPiece = numeroPiece;
            facture.commande_list = commande_list;
            facture.insertDate = insertDate;

            if (id) { facture.id = id; }

            stateMap.facture_cid_map[cid] = facture;
            stateMap.facture_db.insert(facture);
            return facture;
        };

        factory.facture = (function () {
            var init, get_by_cid, get_db, get_facture, _add, _update_list, queryArgs,
                _publish_listchange, _join, _leave, save_cmd, get_commande_by_id, update_cmd;

            queryArgs = {
                pageSize : 50,
                pageNumber : 1
            }

            _update_list = function (arg_list) {
                var i, facture_map, make_facture_map,
                //facture_list = arg_list[0];
                facture_list = arg_list;
                clearfactureDb();
                facture:
                    for (i = 0; i < facture_list.length; i++) {
                        facture_map = facture_list[i];
                        if (!facture_map.nom) { continue facture; }
                        make_facture_map = {
                            cid: makeCid(),
                            id: facture_map.id,
                            nom: facture_map.nom,
                            prenom: facture_map.prenom,
                            genre: facture_map.genre,
                            jourNaissance: facture_map.jourNaissance,
                            moisNaissance: facture_map.moisNaissance,
                            anneeNaissance: facture_map.anneeNaissance,
                            adresse: facture_map.adresse,
                            email: facture_map.email,
                            telephone: facture_map.telephone,
                            paysId: facture_map.paysId,
                            paysLib: facture_map.paysLib,
                            typePieceFournitId: facture_map.typePieceFournitId,
                            typePieceFournitLib: facture_map.typePieceFournitLib,
                            numeroPiece: facture_map.numeroPiece,
                            commande_list: facture_map.commande_list,
                            insertDate: facture_map.insertDate
                        };
                        makefacture(make_facture_map);
                    }
                stateMap.facture_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-facturelistchange', arg_list);
            };

            _add = function (facture) {
                return $http.post(serviceBase + 'postfacture', facture).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-facture-added', response);
                    return response;
                });
            };

            _edit = function (facture) {
                return $http.put(serviceBase + 'putfacture?id=' + facture.id, facture).then(function (response) {
                    console.log("update-facture");
                    console.log(response);
                    $rootScope.$broadcast('app-update-facture', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                //GetAllfacture(int pageSize, int pageNumber)
                pageSize = arg_map.pageSize;
                pageNumber = arg_map.pageNumber;
                searchText = arg_map.searchText;
                laboratoireId = arg_map.laboratoireId;
                employeeId = arg_map.employeeId;
                numeroFacture = arg_map.numeroFacture;
                date = arg_map.date;

                return $http.get(serviceBase + 'facture?limit='
                    + pageSize + '&page='
                    + pageNumber + '&searchText=' + searchText + '&labId=' + laboratoireId + '&empId=' + employeeId + '&numeroFacture=' + numeroFacture + '&date=' + date).then(
                function (results) {
                    console.log(results);

                    var arg_list = results.data;
                    _update_list(arg_list);
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

            update_cmd = function (commande) {
                return $http.put(serviceBase + 'putCommande?id=' + commande.id, commande)
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
                return stateMap.facture_cid_map[cid];
            };
            get_db = function () { return stateMap.facture_db; };

            get_facture = function (facture_id) {
                var factureDB, facture;

                factureDB = get_db();
                facture = factureDB({ id: facture_id }).get();
                console.log(facture);
                return facture;
            };
            
            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_facture: get_facture,
                add: _add,
                edit: _edit,
                save_cmd: save_cmd,
                update_cmd: update_cmd,
                get_commande_by_id: get_commande_by_id,
                join: _join,
                init : init,
                leave : _leave
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

        

        factory.insertfacture = function (facture) {
            return $http.post(serviceBase + 'postfacture', facture).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-facture', response);
                return response;
            });
        };

        factory.newfacture = function () {
            return $q.when({ factureId: 0 });
        };

        

        factory.deletefacture = function (id) {
            return $http.delete(serviceBase + 'deletefacture/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getfacture = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'factureById?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-facture', response);
            });
        };

        return factory;
    };

    factureFactory.$inject = injectParams;

    angular.module('app').factory('factureService', factureFactory);

}());