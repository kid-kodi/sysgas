(function () {

    var injectParams = ['$http', '$q', '$rootScope'];

    var patientFactory = function ($http, $q, $rootScope) {
        var serviceBase = 'api/v1/', patientList = [],
        factory = {
            pageNumber: 1,
            pageSize : 50
        },

        configMap = { anon_id : 'a0' },

        stateMap = {
            anon_patient: null,
            cid_serial: 0,
            patient_cid_map: {},
            patient_db: TAFFY(),
            patient : {}
        },

        patientProto, makeCid, clearPatientDb, removePatient,
        makePatient, patient;

        makeCid = function () {
            return 'c' + String(stateMap.cid_serial++);
        };

        clearPatientDb = function () {
            var patient = stateMap.patient;
            stateMap.patient_db = TAFFY();
            stateMap.patient_cid_map = {};
            if (patient) {
                stateMap.patient_db.insert(patient);
                stateMap.patient_cid_map[patient.cid] = patient;
            }
        };

        removePatient = function (patient) {
            if (!patient) { return false; }
            // can't remove anonymous person
            if (patient.id === configMap.anon_id) {
                return false;
            }
            stateMap.patient_db({ cid: patient.cid }).remove();
            if (patient.cid) {
                delete stateMap.patient_cid_map[patient.cid];
            }
            return true;
        };

        patientProto = {
            get_is_user: function () {
                return this.cid === stateMap.patient.cid;
            },
            get_is_anon: function () {
                return this.cid === stateMap.anon_patient.cid;
            }
        };

        makePatient = function (patient_map) {
            var patient,
            cid = patient_map.cid,
            id = patient_map.id,
            nom = patient_map.nom,
            prenom = patient_map.prenom,
            genre = patient_map.genre,
            jourNaissance = patient_map.jourNaissance,
            moisNaissance = patient_map.moisNaissance,
            anneeNaissance = patient_map.anneeNaissance,
            adresse = patient_map.adresse,
            email = patient_map.email,
            telephone = patient_map.telephone,
            paysId = patient_map.paysId,
            paysLib = patient_map.paysLib,
            typePieceFournitId = patient_map.typePieceFournitId,
            typePieceFournitLib= patient_map.typePieceFournitLib,
            numeroPiece = patient_map.numeroPiece,
            commande_list = patient_map.commande_list,
            insertDate = patient_map.insertDate;

            if (cid === undefined || !nom) {
                throw 'patient id and nom required';
            }

            patient = Object.create(patientProto);
            patient.cid = cid;
            patient.nom = nom;
            patient.prenom = prenom;
            patient.genre = genre;
            patient.jourNaissance = jourNaissance;
            patient.moisNaissance = moisNaissance;
            patient.anneeNaissance = anneeNaissance;
            patient.adresse = adresse;
            patient.email = email;
            patient.telephone = telephone;
            patient.paysId = paysId;
            patient.paysLib = paysLib,
            patient.typePieceFournitId = typePieceFournitId;
            patient.typePieceFournitLib = typePieceFournitLib;
            patient.numeroPiece = numeroPiece;
            patient.commande_list = commande_list;
            patient.insertDate = insertDate;

            if (id) { patient.id = id; }

            stateMap.patient_cid_map[cid] = patient;
            stateMap.patient_db.insert(patient);
            return patient;
        };

        factory.patient = (function () {
            var init, get_by_cid, get_db, get_patient, _add, _update_list, queryArgs,
                _publish_listchange, _join, _leave, save_cmd, get_commande_by_id, update_cmd;

            queryArgs = {
                pageSize : 50,
                pageNumber : 1
            }

            _update_list = function (arg_list) {
                var i, patient_map, make_patient_map,
                //patient_list = arg_list[0];
                patient_list = arg_list;
                clearPatientDb();
                PATIENT:
                    for (i = 0; i < patient_list.length; i++) {
                        patient_map = patient_list[i];
                        if (!patient_map.nom) { continue PATIENT; }
                        make_patient_map = {
                            cid: makeCid(),
                            id: patient_map.id,
                            nom: patient_map.nom,
                            prenom: patient_map.prenom,
                            genre: patient_map.genre,
                            jourNaissance: patient_map.jourNaissance,
                            moisNaissance: patient_map.moisNaissance,
                            anneeNaissance: patient_map.anneeNaissance,
                            adresse: patient_map.adresse,
                            email: patient_map.email,
                            telephone: patient_map.telephone,
                            paysId: patient_map.paysId,
                            paysLib: patient_map.paysLib,
                            typePieceFournitId: patient_map.typePieceFournitId,
                            typePieceFournitLib: patient_map.typePieceFournitLib,
                            numeroPiece: patient_map.numeroPiece,
                            commande_list: patient_map.commande_list,
                            insertDate: patient_map.insertDate
                        };
                        makePatient(make_patient_map);
                    }
                stateMap.patient_db.sort('nom');
            };

            _publish_listchange = function (arg_list) {
                _update_list(arg_list);
                $rootScope.$broadcast('app-patientlistchange', arg_list);
            };

            _add = function (patient) {
                return $http.post(serviceBase + 'patient', patient).then(function (results) {
                    var response = results.data;
                    $rootScope.$broadcast('app-patient-added', response);
                    return response;
                });
            };

            _edit = function (patient) {
                return $http.put(serviceBase + 'patient?id=' + patient.id, patient).then(function (response) {
                    console.log("update-patient");
                    console.log(response);
                    $rootScope.$broadcast('app-update-patient', response.data);
                    return response.data;
                });
            };

            init = function (arg_map) {
                //GetAllPatient(int pageSize, int pageNumber)
                pageSize = arg_map.pageSize;
                pageNumber = arg_map.pageNumber;
                searchText = arg_map.searchText;

                return $http.get(serviceBase + 'patient?page=' + pageNumber + '&limit=' + pageSize + '&searchText=' + searchText).then(
                function (results) {
                    console.log(results);

                    var arg_list = results.data;
                    _update_list(arg_list);
                    return arg_list;
                });
            };

            _leave = function () { };

            save_analyse_list = function (cmd_map) {
                console.log('# commande to save #');
                console.log(cmd_map);
                
                return $http.post(serviceBase + 'cmdanalyse', cmd_map)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-insert-commande', results);
                    });
            };

            update_analyse_list = function (commande) {
                return $http.put(serviceBase + 'cmdanalyse?id=' + commande.id, commande)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-update-commande', results);
                    });
            };

            save_cmd = function (cmd_map) {
                console.log('# commande to save #');
                console.log(cmd_map);
                
                return $http.post(serviceBase + 'patientCmd', cmd_map)
                    .then(function (results) {
                        return results;
                        //$rootScope.$broadcast('app-insert-commande', results);
                    });
            };

            update_cmd = function (commande) {
                return $http.put(serviceBase + 'patientCmd?id=' + commande.id, commande)
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
                return stateMap.patient_cid_map[cid];
            };
            get_db = function () { return stateMap.patient_db; };

            get_patient = function (patient_id) {
                var patientDB, patient;

                patientDB = get_db();
                patient = patientDB({ id: patient_id }).get();
                console.log(patient);
                return patient;
            };
            
            return {
                get_by_cid: get_by_cid,
                get_db: get_db,
                get_patient: get_patient,
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
            return $http.get(serviceBase + 'patient/editconfig').then(
            function (results) {
                console.log(results);
                var response = results.data;
                return response;
            });
        };

        factory.getCmdConfig = function () {
            return $http.get(serviceBase + 'patient/cmdconfig').then(
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

        /*factory.insertCommandeAnalyse = function (type_contrat_id, analyse_id, employee_id) {

            var type_contrat_id = type_contrat_id;
            var analyse_id = analyse_id;
            var employee_id = employee_id;
            
            console.log('selected contrat id ' + type_contrat_id);
            console.log('selected analyse id ' + analyse_id);
            return $http.get(serviceBase + 'patientCmd?tcontratid='
                + type_contrat_id + '&analyseid='
                + analyse_id + '&employeeid='
                + employee_id).then(function (results) {
                    var response = results.data.commandeAnalyse;
                $rootScope.$broadcast('app-insert-commandeAnalyse', response);
                return results.data;
            });
        };*/

        factory.insertCommandeAnalyse = function (commande_id, type_contrat_id, analyse_id , employee_id) {
            var commande_analyse = {
                commande_id : commande_id,
                type_contrat_id : type_contrat_id,
                analyse_id : analyse_id,
                employee_id : employee_id
            };
            return $http.post(serviceBase + 'commandeanalyse', commande_analyse).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-patient', response);
                return response;
            });
        };

        factory.deleteCommandeAnalyse = function (id) {
            return $http.delete(serviceBase + 'commandeanalyse?id=' + id).then(function (results) {
                var response = results.data;
                return response;
            });
        };

        factory.updateAnalyse = function (analyse) {
            return $http.put(serviceBase + 'putAnalyse/' + analyse.id, analyse).then(function (status) {
                $rootScope.$broadcast('app-update-analyse', results.data);
                return status.data;
            });
        };

        

        factory.insertPatient = function (patient) {
            return $http.post(serviceBase + 'postPatient', patient).then(function (results) {
                var response = results.data;
                //$rootScope.$broadcast('app-insert-patient', response);
                return response;
            });
        };

        factory.newPatient = function () {
            return $q.when({ patientId: 0 });
        };

        

        factory.deletePatient = function (id) {
            return $http.delete(serviceBase + 'deletePatient/' + id).then(function (status) {
                return status.data;
            });
        };

        factory.getPatient = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'patientById?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-patient', response);
            });
        };

        factory.getAnalysesByCommandeId = function (id) {
            //then does not unwrap data so must go through .data property
            //success unwraps data automatically (no need to call .data property)
            return $http.get(serviceBase + 'commandeanalyse?id=' + id).then(function (results) {
                var response = results.data;
                $rootScope.$broadcast('app-set-analyse', response);
            });
        };

        return factory;
    };

    patientFactory.$inject = injectParams;

    angular.module('app').factory('patientService', patientFactory);

}());