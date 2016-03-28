(function () {

    var filterAnalyse = function () {

        return function (analyses, filterValue) {
            if (!filterValue) return analyses;

            var matches = [];
            filterValue = filterValue.toLowerCase();
            for (var i = 0; i < analyses.length; i++) {
                var analyse = analyses[i];
                if (analyse.analyseLib.toLowerCase().indexOf(filterValue) > -1 ) {

                    matches.push(analyse);
                }
            }
            return matches;
        };
    };

    angular.module('app').filter('filterAnalyse', filterAnalyse);

}());