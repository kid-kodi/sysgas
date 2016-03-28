angular.module('app').factory('pageService', function () {
        var title = 'default';
        return {
            title: function () { return title; },
            setTitle: function (newTitle) { title = newTitle; }
        };
    });