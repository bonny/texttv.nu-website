// Replace with your client ID from the developer console.
var CLIENT_ID = '242853131826-f83psf4k6mp1vqgk1duq35eolqlpjngu.apps.googleusercontent.com';

// Set authorized scope.
var SCOPES = [
	'https://www.googleapis.com/auth/analytics.readonly',
	'https://www.googleapis.com/auth/adsense.readonly'
];

// Interval in milliseconds for the calls to setInterval
var SECOND = 1000;
var MINUTE = SECOND * 60;
var INTERVALS = {
	"adsense": SECOND * 120,
	"ga_realtime": SECOND * 30,
	"ga_total": MINUTE * 5,
	"texttv": SECOND * 10,
};

function pad(n) {
    return ("0" + n).slice(-2);
}


function authorize(event) {

    // Handles the authorization flow.
    // `immediate` should be false when invoked from the button click.
    var useImmdiate = event ? false : true;
    var authData = {
        client_id: CLIENT_ID,
        scope: SCOPES,
        immediate: useImmdiate
    };

    gapi.auth.authorize(authData, function(response) {
	    
        d( "token expires_in: " + gapi.auth.getToken().expires_in + "s" );
        var expireDate = new Date( gapi.auth.getToken().expires_at * 1000 );
        d( "ie at time " + expireDate );
        
        var authButton = document.getElementById('auth-button');

        if (response.error) {
            authButton.hidden = false;
        } else {
            authButton.hidden = true;

            queryAccounts();
        }

    });
}

function refreshToken() {
	
	d("refreshing token...");
	
    // Handles the authorization flow.
    // `immediate` should be false when invoked from the button click.
    var authData = {
        client_id: CLIENT_ID,
        scope: SCOPES,
        immediate: true
    };

    gapi.auth.authorize(authData, function(response) {
	    
	    d("got new token data");
        d( "token expires_in: " + gapi.auth.getToken().expires_in + "s" );
        var expireDate = new Date( gapi.auth.getToken().expires_at * 1000 );
        d( "ie at time " + expireDate );
        
        var authButton = document.getElementById('auth-button');

        if (response.error) {
            authButton.hidden = false;
        } else {
            authButton.hidden = true;
        }

    });
}

function queryAccounts() {

    // Load the Google Analytics client library.
    gapi.client.load('analytics', 'v3').then(function() {

        // Get a list of all Google Analytics accounts for this user
        gapi.client.analytics.management.accounts.list().then(handleAccounts);

    });

	gapi.client.load('adsense', 'v1.4', initAdSense);

}

function initAdSense() {
	
	queryAdSense();
    setInterval(queryAdSense, INTERVALS.adsense);
	
}

function queryAdSense() {

    var request = gapi.client.adsense.reports.generate({
      'startDate': "today-1m",
      'endDate': "today",
      'dimension': ["DATE"],
	  'metric': ["EARNINGS"]
    });
        
	request.execute(function(resp) {

		var elm = $(".ga-adsense .ga-value");
		var value = s.numberFormat( parseFloat(resp.rows[resp.rows.length-1][1]), 0);

		elm.html(value + ":-");

	});
		
}

function handleAccounts(response) {
    // Handles the response from the accounts list method.
    if (response.result.items && response.result.items.length) {
        // Get the first Google Analytics account.
        var firstAccountId = response.result.items[0].id;

        // Query for properties.
        queryProperties(firstAccountId);
    } else {
        console.log('No accounts found for this user.');
    }
}


function queryProperties(accountId) {

    // Get a list of all the properties for the account.
    gapi.client.analytics.management.webproperties.list({
            'accountId': accountId
        })
        .then(handleProperties)
        .then(null, function(err) {
            // Log any errors.
            onApiError(err, "queryProperties");
        });

}


function handleProperties(response) {

    // Handles the response from the webproperties list method.
    if (response.result.items && response.result.items.length) {

        // Get the first Google Analytics account
        var firstAccountId = response.result.items[0].accountId;

        // 26 = texttv material design
        // 20 = texttv ios app v2
        // 13 = texttv.nu, name = texttv.nu
        var texttvnuAccount = response.result.items.filter(function(val) {
	        // TextTV.nu ios app v2
	        // SVT Text TV by TextTV.nu (material design version)
            return (val.name == "texttv.nu");
        });

        var texttvnuIosAppV2Account = response.result.items.filter(function(val) {
	        // SVT Text TV by TextTV.nu (material design version)
            return (val.name == "TextTV.nu ios app v2");
        });
		
        // Get and show stats for web site
        queryProfiles(firstAccountId, texttvnuAccount[0].id, handleProfilesWeb);

        // Get and show stats for ios app v2
        queryProfiles(firstAccountId, texttvnuIosAppV2Account[0].id, handleProfilesIosV2App);

    } else {
        console.log('No properties found for this user.');
    }

}


function queryProfiles(accountId, propertyId, handleProfiles) {

    // Get a list of all Views (Profiles) for the first property
    // of the first Account.
    gapi.client.analytics.management.profiles.list({
            'accountId': accountId,
            'webPropertyId': propertyId
        })
        .then(handleProfiles)
        .then(null, function(err) {
            // Log any errors.
            onApiError(err, "queryProfiles");
        });
}


function handleProfilesWeb(response) {

    // Handles the response from the profiles list method.
    if (response.result.items && response.result.items.length) {

        // Get the first View (Profile) ID.
        //console.log("response.result", response.result);
        var firstProfileId = response.result.items[0].id;

		// Every 20 seconds real time stats
        queryCoreReportingApiRealtime(firstProfileId, $(".ga-visits-now .ga-value"));
        setInterval(queryCoreReportingApiRealtime, INTERVALS.ga_realtime, firstProfileId, $(".ga-visits-now .ga-value"));
		
		// Once a minute daily stats
        queryCoreReportingApiToday(firstProfileId, $(".ga-pageviews-today .ga-value"), $(".ga-pageviewsPerSession-today .ga-value"), "ga:pageviews", "ga:pageviewsPerSession");
        setInterval(queryCoreReportingApiToday, INTERVALS.ga_total, firstProfileId, $(".ga-pageviews-today .ga-value"), $(".ga-pageviewsPerSession-today .ga-value"), "ga:pageviews", "ga:pageviewsPerSession");
	
    } else {
        console.log('No views (profiles) found for this user.');
    }
    
}

function handleProfilesIosV2App(response) {

    // Handles the response from the profiles list method.
    if (response.result.items && response.result.items.length) {

        // Get the first View (Profile) ID.
        //console.log("response.result", response.result);
        var firstProfileId = response.result.items[0].id;

		// Every 20 seconds real time stats
        queryCoreReportingApiRealtime(firstProfileId, $(".ga-visits-now-app .ga-value"));
        setInterval(queryCoreReportingApiRealtime, INTERVALS.ga_realtime, firstProfileId, $(".ga-visits-now-app .ga-value"));
		
		// Once a minute daily stats
        queryCoreReportingApiToday(firstProfileId, $(".ga-pageviews-today-app .ga-value"), $(".ga-pageviewsPerSession-today-app .ga-value"), "ga:screenviews", "ga:screenviewsPerSession");
        setInterval(queryCoreReportingApiToday, INTERVALS.ga_total, firstProfileId, $(".ga-pageviews-today-app .ga-value"), $(".ga-pageviewsPerSession-today-app .ga-value"), "ga:screenviews", "ga:screenviewsPerSession");
	
    } else {
        console.log('No views (profiles) found for this user.');
    }
    
}

// Get visitors today
function queryCoreReportingApiToday(profileId, elmPageviews, elmPageviewsPerSession, metric1, metric2) {
	
    // Query the Core Reporting API
    // https://www.googleapis.com/analytics/v3/data/ga?ids=ga%3A54301347
    //&start-date=today&end-date=today&metrics=ga%3Asessions%2Cga%3Apageviews%2Cga%3ApageviewsPerSession
    gapi.client.analytics.data.ga.get({
        'ids': 'ga:' + profileId,
        'start-date': 'today',
        'end-date': 'today',
        'metrics': 'ga:users,ga:sessions,ga:pageviews,ga:pageviewsPerSession,ga:screenviews,ga:screenviewsPerSession'
    })
    .then(function(response) {

        var gaValue = "";

        if (response.status !== 200) {
            gaValue = "Dang! Error while fetching GA data.";
        } else {
            gaValue = parseInt( response.result.totalsForAllResults[metric1] );
            gaValue = s.numberFormat(gaValue, 0, ",", " ");
        }

        elmPageviews.html( gaValue );

        if (response.status !== 200) {
            gaValue = "Dang! Error while fetching GA data.";
        } else {
            gaValue = parseFloat( response.result.totalsForAllResults[metric2] );
            gaValue = s.numberFormat(gaValue, 1, ",", " ");
        }

        elmPageviewsPerSession.html( gaValue );

    })
    .then(null, function(err) {
        // Log any errors.
        onApiError(err, "queryCoreReportingApiToday");
    });
    
}

function queryCoreReportingApiRealtime(profileId, elmActiveUsers) {

    // Get real time visitors
    var promise = gapi.client.analytics.data.realtime.get({
        'ids': 'ga:' + profileId,
        "metrics": 'rt:activeUsers',
    }).then(function(response) {

        var gaValue = "";
        if (response.status !== 200) {
            gaValue = "Dang! Error while fetching GA data.";
        } else {
            gaValue = parseInt( response.result.totalsForAllResults["rt:activeUsers"] );
            gaValue = s.numberFormat(gaValue, 0, ",", " ");
        }

        elmActiveUsers.html( gaValue );

        var d = new Date();
        
        $(".ga-date-updated-value").text( pad(d.getHours()) + ":" + pad(d.getMinutes()) + ":" + pad(d.getSeconds()));

    }).then(null, function(err) {
        // Log any errors.
		onApiError(err, "queryCoreReportingApiRealtime");
    });

}

function initTexttv() {

	getLatestTextTvNews();
	
	setInterval(getLatestTextTvNews, INTERVALS.texttv);
	
	$(".close").on("click", function() {
		window.close();
	});
	
}

function getLatestTextTvNews() {
	
	$.getJSON("https://api.texttv.nu/api/get/100?app=dashboard.rasptouch").then(function(data) {
	
	  if (data && data[0] && data[0].content) {
		  
		  // on page 100 some lines are not content (text tv logo)
		  var content = data[0].content.join();
		  var lines = content.split("\n");
		  
		  lines = lines.slice(0,1).concat(lines.slice(5,20));

		  $(".texttv-latest-news").html(lines.join("\n"));
		  
	  }
	
	});

}

function onApiError(err, fromFunc) {
	
	// If error was code 401 Invalid Credentials then maybe we have been running for an hours
	// and our refresh token has expired
	//if (err && err.status == 401 && err.result && err.result.error && err.result.error.errors && err.result.error.errors.reason && err.result.error.errors.reason == "authError") {
	if (err && err.status == 401) {
		d("got error 401 from api via " + fromFunc + ": " + JSON.stringify(err.result));
		d( JSON.stringify(err.result.error) );
		d( JSON.stringify(err.result.error.errors) );
		d( JSON.stringify(err.result.error.errors[0].reason) );
		refreshToken();
	//} else if (err && err.status == 403 && err.result && err.result.error && err.result.error.errors && err.result.error.errors.reason && err.result.error.errors.reason == "userRateLimitExceeded") {
	} else if (err && err.status == 403) {

		// get here when error is userRateLimitExceeded or similar
		// google says that we should do exponential backoff until it gives no error again
		// https://developers.google.com/analytics/devguides/reporting/core/v3/coreErrors#backoff
		d("got error 403 from api via " + fromFunc + ": " + JSON.stringify(err.result));
		d( JSON.stringify(err.result.error.errors[0].reason) );
		d("dunno what to do...");
		// refreshToken();
		
	} else {
		
		// unhandled error

		var elm = $(".api-error-output");
		var html = elm.html() + "\n\nunhandled error in " + fromFunc + "\n" + JSON.stringify(err);

		elm.html(html);
		elm.addClass("has-errors");

	}
	
}

function d(str) {
	var debugElm = $(".debug-output");
	var html = debugElm.html() + "\n\n" + str;
	debugElm.html(html);
}

initTexttv();

// Add an event listener to the 'auth-button'.
document.getElementById('auth-button').addEventListener('click', authorize);

