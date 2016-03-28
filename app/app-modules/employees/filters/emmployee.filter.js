(function () {

    var filterEmployee = function () {

        return function (employees, filterValue) {
            if (!filterValue) return employees;

            var matches = [];
            filterValue = filterValue.toLowerCase();
            for (var i = 0; i < employees.length; i++) {
                var employee = employees[i];
                if (employee.fullName.toLowerCase().indexOf(filterValue) > -1 ) {

                    matches.push(employee);
                }
            }
            return matches;
        };
    };

    angular.module('app').filter('filterEmployee', filterEmployee);

}());