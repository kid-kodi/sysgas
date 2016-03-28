(function () {

    var injectParams = ['$http', '$q', '$rootScope'];

    var employeeFactory = function ($http, $q, $rootScope) {
        var serviceBase = 'api/v1/', employeeList = [],
        factory = {
            pageNumber: 1,
            pageSize : 50
        },

        configMap = { anon_id : 'a0' },

        stateMap = {
            anon_employee: null,
            cid_serial: 0,
            employee_cid_map: {},
            employee_db: TAFFY(),
            employee: {}
        },

        employeeProto, makeCid, clearemployeeDb, removeemployee,
        makeemployee, employee;

        makeCid = function () {
            return 'c' + String(stateMap.cid_serial++);
        };

        clearemployeeDb = function () {
            var employee = stateMap.employee;
            stateMap.employee_db = TAFFY();
            stateMap.employee_cid_map = {};
            if (employee) {
                stateMap.employee_db.insert(employee);
                stateMap.employee_cid_map[employee.cid] = employee;
            }
        };

        removeemployee = function (employee) {
            if (!employee) { return false; }
            // can't remove anonymous person
            if (employee.id === configMap.anon_id) {
                return false;
            }
            stateMap.employee_db({ cid: employee.cid }).remove();
            if (employee.cid) {
                delete stateMap.employee_cid_map[employee.cid];
            }
            return true;
        };

        employeeProto = {
            get_is_user: function () {
                return this.cid === stateMap.employee.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_employee.cid;
            }
        };

        makeemployee = function (employee_map) {
            var employee,
            cid = employee_map.cid,
            id = employee_map.id,
            nom = employee_map.nom,
            prenom = employee_map.prenom,
            genre = employee_map.genre,
            jourNaissance = employee_map.jourNaissance,
            moisNaissance = employee_map.moisNaissance,
            anneeNaissance = employee_map.anneeNaissance,
            adresse = employee_map.adresse,
            email = employee_map.email,
            telephone = employee_map.telephone,
            paysId = employee_map.paysId,
            paysLib = employee_map.paysLib,
            typePieceFournitId = employee_map.typePieceFournitId,
            typePieceFournitLib= employee_map.typePieceFournitLib,
            numeroPiece = employee_map.numeroPiece,
            commande_list = employee_map.commande_list,
            insertDate = employee_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'employee id and nom required';
            }

            employee = Object.create(employeeProto);
            employee.cid = cid;
            employee.nom = nom;
            employee.prenom = prenom;
            employee.genre = genre;
            employee.jourNaissance = jourNaissance;
            employee.moisNaissance = moisNaissance;
            employee.anneeNaissance = anneeNaissance;
            employee.adresse = adresse;
            employee.email = email;
            employee.telephone = telephone;
            employee.paysId = paysId;
            employee.paysLib = paysLib,
            employee.typePieceFournitId = typePieceFournitId;
            employee.typePieceFournitLib = typePieceFournitLib;
            employee.numeroPiece = numeroPiece;
            employee.commande_list = commande_list;
            employee.insertDate = insertDate;

            if (id) { employee.id = id; }

            stateMap.employee_cid_map[cid] = employee;
            stateMap.employee_db.insert(employee);
            return employee;
        };

        factory.employee = (function () {
            var init, get_by_cid, get_db, get_employee, _add, _update_list, queryArgs,
                _publish_listchange, _join, _leave, save_cmd, get_commande_by_id, update_cmd;

            queryArgs = {
                pageSize : 50,
                pageNumber : 1
            }

            _update_list = function (arg_list) {
                var i, employee_map, make_employee_map,
                //employee_list = arg_list[0];
                employee_list = arg_list;
                clearemployeeDb();
                employee:
                    for (i = 0; i < employee_list.length; i++) {
                        employee_map = employee_list[i];
                        if (!employee_map.nom) { continue employee; }
                        make_employee_map = {
                            cid: makeCid(),
                            id: employee_map.id,
                            nom: employee_map.nom,
                            prenom: employee_map.prenom,
                            genre: employee_map.genre,
                            jourNaissance: employee_map.jourNaissance,
                            moisNaissance: employee_map.moisNaissance,
                            anneeNaissance: employee_map.anneeNaissance,
                            adresse: employee_map.adresse,
                            email: employee_map.email,
                            telephone: employee_map.telephone,
                            paysId: employee_map.paysId,
                            paysLib: employee_map.paysLib,
                            typePieceFournitId: employee_map.typePieceFournitId,
                            typePieceFournitLib: employee_map.typePieceFournitLib,
                            numeroPiece: employee_map.numeroPiece,
                            commande_list: employee_map.commande_list,
                            insertDate: employee_map.insertDate
                        };
                        makeemployee(make_employee_map);
                    }
                stateMap.employee_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-employeelistchange', arg_list);
            };

            _add = function (employee) {
                return $http.post(serviceBase + 'employee', employee).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-employee-added', response);
                    return response;
                });
            };

            _edit = function (employee) {
                return $http.put(serviceBase + 'employee?id=' + employee.id, employee).then(function (response) {
                    console.log("update-employee");
                    console.log(response);
                    $rootScope.$broadcast('app-update-employee', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                //GetAllemployee(int pageSize, int pageNumber)
                pageSize = arg_map.pageSize;
                pageNumber = arg_map.pageNumber;
                searchText = arg_map.searchText;

                return $http.get(serviceBase + 'employee?limit=' + pageSize + '&page=' + pageNumber + '&searchText=' + searchText).then(
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
                
                return $http.post(serviceBase + 'account', acct_map)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-insert-commande', results);
                    });
            };

            update_acct = function (acct_map) {
                return $http.put(serviceBase + 'account?id=' + acct_map.id, acct_map)
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
                return stateMap.employee_cid_map[cid];
            };
            get_db = function () { return stateMap.employee_db; };

            get_employee = function (employee_id) {
                var employeeDB, employee;

                employeeDB = get_db();
                employee = employeeDB({ id: employee_id }).get();
                console.log(employee);
                return employee;
            };
            
            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_employee: get_employee,
                add: _add,
                edit: _edit,
                save_acct: save_acct,
                update_acct: update_acct,
                join: _join,
                init : init,
                leave : _leave
            };
        }());

        factory.getAccount = function (employeeId) {
            return $http.get(serviceBase + 'account?id=' + employeeId).then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };

        factory.getEditConfig = function () {
            return $http.get(serviceBase + 'employee/editconfig').then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };

        factory.getAcctConfig = function () {
            return $http.get(serviceBase + 'account/editconfig').then(
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

        

        factory.insertemployee = function (employee) {
            return $http.post(serviceBase + 'postemployee', employee).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-employee', response);
                return response;
            });
        };

        factory.newemployee = function () {
            return $q.when({ employeeId: 0 });
        };

        

        factory.deleteemployee = function (id) {
            return $http.delete(serviceBase + 'deleteemployee/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getemployee = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'employeeById?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-employee', response);
            });
        };

        return factory;
    };

    employeeFactory.$inject = injectParams;

    angular.module('app').factory('employeeService', employeeFactory);

}());