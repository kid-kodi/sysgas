(function () {

    var filterRole = function () {

        return function (roles, filterValue) {
            if (!filterValue) return roles;

            var matches = [];
            filterValue = filterValue.toLowerCase();
            for (var i = 0; i < roles.length; i++) {
                var role = roles[i];
                if (role.roleName.toLowerCase().indexOf(filterValue) > -1 ) {

                    matches.push(role);
                }
            }
            return matches;
        };
    };

    angular.module('app').filter('filterRole', filterRole);

}());