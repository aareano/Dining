package valrae.tufts.dining;

import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.util.List;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.ClientProtocolException;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.client.utils.URLEncodedUtils;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;
import org.apache.http.util.EntityUtils;
import android.app.ProgressDialog;
import android.content.Context;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.net.wifi.WifiInfo;
import android.net.wifi.WifiManager;
import android.os.AsyncTask;
import android.util.Log;
import android.widget.Toast;

public class ServiceManager {
	
	// HTTP call URLs
	private final String VOTE_URL 			= "http://hellobiped.com/tufts_dining/vote";
	private final String TALLY_VOTES_URL 	= "http://hellobiped.com/tufts_dining/tally_votes";
	private final String RATE_URL 			= "http://hellobiped.com/tufts_dining/rate";
	private final String TALLY_RATINGS_URL 	= "http://hellobiped.com/tufts_dining/tally_ratings";

	public final static String GET  = HttpGet.METHOD_NAME;
	public final static String POST = HttpPost.METHOD_NAME;
    
    // of Activity
    private final Context CONTEXT;

    // Functionality of Activity
    private final String FUNCTIONALITY;
    private final String COMPARISON;
    private final String RATING;
	
    private final String TAG = "ServiceManager";
    
    private String postJson;
    private String getJson;
    
    private NetworkInfo networkInfo;
    ProgressDialog pDialog;
    
    /**
     * Creates new ServiceManager object
     * @param context of activity that this is working in
     * @param function is functionality of the activity that is using ServiceManager
     */
    public ServiceManager (Context context, String functionality) {
    	Log.i(TAG, "new ServiceManager()");
    	// store activity context and ServiceManager function
    	this.CONTEXT = context;
    	this.FUNCTIONALITY = functionality;
    	this.COMPARISON = CONTEXT.getResources().getString(R.string.comparison);
    	this.RATING = CONTEXT.getResources().getString(R.string.rating);
    	
    	// get network information
    	ConnectivityManager connMgr = (ConnectivityManager) 
    			CONTEXT.getSystemService(Context.CONNECTIVITY_SERVICE);
        networkInfo = connMgr.getActiveNetworkInfo();
    }
    
    /**
     * Sends data to AsyncTask
     * @param data
     */
    @SuppressWarnings("unchecked")
	public void startService (List<NameValuePair> data) {
    	
    	// get mac address
    	String mac = getMacAddress();
		data.add(new BasicNameValuePair(CONTEXT.getResources().getString(R.string.mac_key), 
				mac));
    	
		Log.i(TAG, "data in startService: " + data.toString());
		
    	// check network status
    	if (networkInfo != null && networkInfo.isConnected()) {
        	new statService().execute(data);						// get data
        } else {
            Toast.makeText(CONTEXT, "No network connection available.", 
            		Toast.LENGTH_LONG).show(); 						// display error
        }    	
    }
    
    /**
     * Get device MAC address
     * @return MAC address
     */
    public String getMacAddress() {
    	
    	WifiManager manager = (WifiManager) CONTEXT.getSystemService(Context.WIFI_SERVICE);
    	String address = null;
    	
    	if(manager.isWifiEnabled()) {

    		// WIFI ALREADY ENABLED. GRAB THE MAC ADDRESS HERE
    		WifiInfo info = manager.getConnectionInfo();
    		address = info.getMacAddress();
    		
    	} else {
    		// ENABLE THE WIFI FIRST
    		manager.setWifiEnabled(true);
    		
    		// WIFI IS NOW ENABLED. GRAB THE MAC ADDRESS HERE
    		WifiInfo info = manager.getConnectionInfo();
    		address = info.getMacAddress();
    	}    	
    	
    	Log.d("Mac Address:", "mac address:" + address);
    	return address; 
    }
    
    /**
     * AsyncTask to get JSON containing desired statistics by making HTTP requests
     * */
    protected class statService extends AsyncTask <List<NameValuePair>, Void, String[]> {

    	@Override
        protected void onPreExecute() {
            super.onPreExecute();
            // Showing progress dialog
            pDialog = new ProgressDialog(CONTEXT);
            pDialog.setMessage("Please wait...");
            pDialog.setCancelable(false);
            pDialog.show();
        }
    	
    	/* Order of params[0] is something like: {dewick, carm, mac} */
		@Override
		protected String[] doInBackground(List<NameValuePair>... params) {
			Log.d(TAG, "doInBackground");
			
			String postJson = null;
			String getJson = null;
			
			if (FUNCTIONALITY.equals(COMPARISON)) {
				// make POST >> returns JSON response
				postJson = makeServiceCall(VOTE_URL, POST, params[0]);
				Log.d("ServiceManager", FUNCTIONALITY + "POST response > " + postJson);
			
				// make GET >> returns JSON response
				getJson = makeServiceCall(TALLY_VOTES_URL, GET);
				Log.d("ServiceManager", FUNCTIONALITY + "GET response > " + getJson);
				
			} else if (FUNCTIONALITY.equals(RATING)) {
				// make POST >> returns JSON response
				postJson = makeServiceCall(RATE_URL, POST, params[0]);
				Log.d("ServiceManager", FUNCTIONALITY + "POST response > " + postJson);
			
				// make GET >> returns JSON response
				getJson = makeServiceCall(TALLY_RATINGS_URL, GET);
				Log.d("ServiceManager", FUNCTIONALITY + "GET response > " + getJson);
			}
			
			String jsonResponses[] = {postJson, getJson};
			return jsonResponses;
        }
 
        @Override
        protected void onPostExecute(String jsonResponses[]) {
            super.onPostExecute(jsonResponses);
            
            Log.i(TAG, "jsonResponses[post, get]: " + jsonResponses);
            
            // Dismiss the progress dialog
            if (pDialog.isShowing())
                pDialog.dismiss();
            
            // store JSON strings in member variables
            postJson = jsonResponses[0];
            getJson  = jsonResponses[1];
        }
    }
    
    /**
     * Making service call
     * @url - URL to make request
     * @method - HTTP request method
     * */
    public String makeServiceCall(String url, String method) {
        return this.makeServiceCall(url, method, null);
    }
    
    /**
     * Making service call
     * @url - URL to make request
     * @method - HTTP request method
     * @params - HTTP request params
     * */
    public String makeServiceCall(String url, String method, List<NameValuePair> params) {
        
    	String response =  null;
    	
    	try {
            // HTTP client
            DefaultHttpClient httpClient = new DefaultHttpClient();
            HttpEntity httpEntity = null;
            HttpResponse httpResponse = null;
             
            // check method >> POST
            if (method == POST) {
            	HttpPost httpPost = new HttpPost(url);
                
            	// adding params to POST
                if (params != null) {
                    httpPost.setEntity(new UrlEncodedFormEntity(params));
                }               
                // make request, get response
                httpResponse = httpClient.execute(httpPost);
 
            // check method >> GET
            } else if (method == GET) {
                
            	// appending params to URL
                if (params != null) {				// this won't actually ever be used
                    String paramString = URLEncodedUtils.format(params, "utf-8");
                    url += "?" + paramString;		// how does this comes out?
                }
                HttpGet httpGet = new HttpGet(url);
                
                // make request, get response
                httpResponse = httpClient.execute(httpGet);
            }
            
            // convert httpEntity to String and return
            httpEntity = httpResponse.getEntity();
            response = EntityUtils.toString(httpEntity);
 
        } catch (UnsupportedEncodingException e) {
            e.printStackTrace();
        } catch (ClientProtocolException e) {
            e.printStackTrace();
        } catch (IOException e) {
            e.printStackTrace();
        }
        return response;
    }
    
    /* -------------------------- Getter methods -------------------------- */
    
    // get POST results
    public String getPOSTJson() {
		return postJson;
    }
    
    // get GET results
    public String getGETJson() {
    	return getJson;
    }
}