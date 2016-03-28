(function () {

    var injectParams = ['$scope', '$filter', 'patientService','patient_list'];

    var patientListeController = function ($scope, $filter, patientService, patient_list) {
        var vm = this;
        vm.patients = patient_list.patients;
        vm.TotalCount = patient_list.totalCount;
        vm.TotalPages = patient_list.totalPages;

        vm.filteredPatient = [];
        vm.searchText = null;

        vm.selectedIndex = 0;
        vm.pageSize = 50;
        vm.pageNumber = 1;
        vm.showSearchForm = false;

        vm.of = vm.pageNumber;
        vm.to = (vm.TotalCount < vm.pageSize) ? vm.TotalCount : vm.pageSize;//((now.getHours() > 17) ? " evening." : " day.")

        /*hub = $.connection.PatientHub;
        $.connection.hub.start().done(function () {
            console.log('app started');
        });*/


        filterPatients("");

        vm.toggleSeachForm = function () {
            vm.showSearchForm = !vm.showSearchForm;
        };


        vm.searchTextChanged = function () {
            filterPatients(vm.searchText);
        };


        vm.itemClicked = function (patient_id) {
            vm.selectedIndex = patient_id;
        };

        vm.navigateTo = function (direction) {

            if (direction == 'next') {
                vm.of = vm.of + vm.pageSize;
                vm.to = vm.to + vm.pageSize;
                vm.pageNumber = vm.pageNumber + 1;
            }
            else {
                if (vm.pageNumber == 0) { return false;}
                vm.pageNumber = vm.pageNumber - 1;
                vm.of = vm.of - vm.pageSize;
                vm.to = vm.to - vm.pageSize;
            }
            
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText: vm.searchText
            }
            var patient_list =
                patientService.patient.init(arg_map)
                .then(function (patient_list) {
                    vm.patients = patient_list.patients;
                    filterPatients('');
                });
            //console.log(patient_list);
            return patient_list;
        };

        vm.lookUp = function () {
            var arg_map = {
                pageSize: vm.pageSize,
                pageNumber: vm.pageNumber,
                searchText : vm.searchText
            }
            var patient_list =
                patientService.patient.init(arg_map)
                .then(function (patient_list) {
                    vm.patients = patient_list.patients;
                    filterPatients('');
                });
            //console.log(patient_list);
            return patient_list;
        };

        function filterPatients(filterText) {
            //console.log(vm.patients);
            vm.filteredCount = vm.patients.length;

            if (vm.filteredCount > 0) {
                vm.filteredPatient = $filter("filterPatient")(vm.patients, filterText);
            }
            vm.init = true;
            //alert();
        }

        (function() {
            vm.pageSize = patientService.pageSize;
            vm.pageNumber = patientService.pageNumber;
            vm.searchText = "";
        })();

    };

    patientListeController.$inject = injectParams;

    angular.module('app').controller('patientListeController', patientListeController);

})();