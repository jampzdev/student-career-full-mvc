psslai.filter('dateFormat', function() {
    return function(value) {
        return moment(value).format('ll');
    }
})
.filter('commaLess', function() {
    return function(input) {
    return (input)?input.toString().trim().replace(",",""):null;
    };
 })
.filter('statusFilter',function(){
    return function(value) {
        if(value == null){
            return;
        }
        if(value.toLowerCase() == 'a'){
            return 'Activated';
        }
        if(value.toLowerCase() == 'd'){
            return 'Deactivated';
        }
    }
});