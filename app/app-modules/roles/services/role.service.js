(function () {

    var injectParams = ['$http', '$q', '$rootScope'];

    var roleFactory = function ($http, $q, $rootScope) {
        var serviceBase = 'api/v1/', roleList = [],
        factory = {
            pageNumber: 1,
            pageSize : 50
        },

        configMap = { anon_id : 'a0' },

        stateMap = {
            anon_role: null,
            cid_serial: 0,
            role_cid_map: {},
            role_db: TAFFY(),
            role: {}
        },

        roleProto, makeCid, clearroleDb, removerole,
        makerole, role;

        makeCid = function () {
            return 'c' + String(stateMap.cid_serial++);
        };

        clearroleDb = function () {
            var role = stateMap.role;
            stateMap.role_db = TAFFY();
            stateMap.role_cid_map = {};
            if (role) {
                stateMap.role_db.insert(role);
                stateMap.role_cid_map[role.cid] = role;
            }
        };

        removerole = function (role) {
            if (!role) { return false; }
            // can't remove anonymous person
            if (role.id === configMap.anon_id) {
                return false;
            }
            stateMap.role_db({ cid: role.cid }).remove();
            if (role.cid) {
                delete stateMap.role_cid_map[role.cid];
            }
            return true;
        };

        roleProto = {
            get_is_user: function () {
                return this.cid === stateMap.role.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_role.cid;
            }
        };

        makerole = function (role_map) {
            var role,
            cid = role_map.cid,
            id = role_map.id,
            nom = role_map.nom,
            prenom = role_map.prenom,
            genre = role_map.genre,
            jourNaissance = role_map.jourNaissance,
            moisNaissance = role_map.moisNaissance,
            anneeNaissance = role_map.anneeNaissance,
            adresse = role_map.adresse,
            email = role_map.email,
            telephone = role_map.telephone,
            paysId = role_map.paysId,
            paysLib = role_map.paysLib,
            typePieceFournitId = role_map.typePieceFournitId,
            typePieceFournitLib= role_map.typePieceFournitLib,
            numeroPiece = role_map.numeroPiece,
            commande_list = role_map.commande_list,
            insertDate = role_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'role id and nom required';
            }

            role = Object.create(roleProto);
            role.cid = cid;
            role.nom = nom;
            role.prenom = prenom;
            role.genre = genre;
            role.jourNaissance = jourNaissance;
            role.moisNaissance = moisNaissance;
            role.anneeNaissance = anneeNaissance;
            role.adresse = adresse;
            role.email = email;
            role.telephone = telephone;
            role.paysId = paysId;
            role.paysLib = paysLib,
            role.typePieceFournitId = typePieceFournitId;
            role.typePieceFournitLib = typePieceFournitLib;
            role.numeroPiece = numeroPiece;
            role.commande_list = commande_list;
            role.insertDate = insertDate;

            if (id) { role.id = id; }

            stateMap.role_cid_map[cid] = role;
            stateMap.role_db.insert(role);
            return role;
        };

        factory.role = (function () {
            var init, get_by_cid, get_db, get_role, _add, _update_list, queryArgs,
                _publish_listchange, _join, _leave, save_cmd, get_commande_by_id, update_cmd;

            queryArgs = {
                pageSize : 50,
                pageNumber : 1
            }

            _update_list = function (arg_list) {
                var i, role_map, make_role_map,
                //role_list = arg_list[0];
                role_list = arg_list;
                clearroleDb();
                role:
                    for (i = 0; i < role_list.length; i++) {
                        role_map = role_list[i];
                        if (!role_map.nom) { continue role; }
                        make_role_map = {
                            cid: makeCid(),
                            id: role_map.id,
                            nom: role_map.nom,
                            prenom: role_map.prenom,
                            genre: role_map.genre,
                            jourNaissance: role_map.jourNaissance,
                            moisNaissance: role_map.moisNaissance,
                            anneeNaissance: role_map.anneeNaissance,
                            adresse: role_map.adresse,
                            email: role_map.email,
                            telephone: role_map.telephone,
                            paysId: role_map.paysId,
                            paysLib: role_map.paysLib,
                            typePieceFournitId: role_map.typePieceFournitId,
                            typePieceFournitLib: role_map.typePieceFournitLib,
                            numeroPiece: role_map.numeroPiece,
                            commande_list: role_map.commande_list,
                            insertDate: role_map.insertDate
                        };
                        makerole(make_role_map);
                    }
                stateMap.role_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-rolelistchange', arg_list);
            };

            _add = function (roleName, selection) {
                var role = {
                    roleName     : roleName,
                    capabilities : selection
                };
                return $http.post(serviceBase + 'role', role).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-role-added', response);
                    return response;
                });
            };

            _edit = function (id, roleName, selection) {

                var role = {
                    id: id,
                    roleName: roleName,
                    capabilities: selection
                };

                return $http.put(serviceBase + 'role?id=' + role.id, role).then(function (response) {
                    console.log("update-role");
                    console.log(response);
                    $rootScope.$broadcast('app-update-role', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                //GetAllrole(int pageSize, int pageNumber)
                pageSize = arg_map.pageSize;
                pageNumber = arg_map.pageNumber;
                searchText = arg_map.searchText;

                return $http.get(serviceBase + 'role?limit=' + pageSize + '&page=' + pageNumber + '&searchText=' + searchText).then(
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
                return stateMap.role_cid_map[cid];
            };
            get_db = function () { return stateMap.role_db; };

            get_role = function (role_id) {
                var roleDB, role;

                roleDB = get_db();
                role = roleDB({ id: role_id }).get();
                console.log(role);
                return role;
            };
            
            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_role: get_role,
                add: _add,
                edit: _edit,
                save_acct: save_acct,
                update_acct: update_acct,
                join: _join,
                init : init,
                leave : _leave
            };
        }());

        factory.getAccount = function (roleId) {
            return $http.get(serviceBase + 'empaccount?id=' + roleId).then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };

        factory.getEditConfig = function () {
            return $http.get(serviceBase + 'role/editconfig').then(
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

        factory.insertCommandeAnalyse = function (type_contrat_id, analyse_id, role_id) {

            var type_contrat_id = type_contrat_id;
            var analyse_id = analyse_id;
            var role_id = role_id;
            
            console.log('selected contrat id ' + type_contrat_id);
            console.log('selected analyse id ' + analyse_id);
            return $http.get(serviceBase + 'postCommandeAnalyse?tcontratid='
                + type_contrat_id + '&analyseid='
                + analyse_id + '&roleid='
                + role_id).then(function (results) {
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

        

        factory.insertrole = function (role) {
            return $http.post(serviceBase + 'postrole', role).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-role', response);
                return response;
            });
        };

        factory.newrole = function () {
            return $q.when({ roleId: 0 });
        };

        

        factory.deleterole = function (id) {
            return $http.delete(serviceBase + 'deleterole/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getrole = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'roleById?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-role', response);
            });
        };

        return factory;
    };

    roleFactory.$inject = injectParams;

    angular.module('app').factory('roleService', roleFactory);

}());