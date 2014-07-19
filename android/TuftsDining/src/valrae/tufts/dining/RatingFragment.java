package valrae.tufts.dining;

import java.util.ArrayList;
import java.util.List;

import org.apache.http.NameValuePair;
import org.apache.http.message.BasicNameValuePair;
import org.json.JSONException;
import org.json.JSONObject;

import android.app.Activity;
import android.content.Context;
import android.os.Bundle;
import android.os.Handler;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;

/**
 * Fragment for rating function: user votes for a venue as 'good' or 'bad'.
 * Vote is stored at mysql.hellobiped.com
 * @author Aaron
 */
public class RatingFragment extends Fragment {

	private final String TAG = "ComparisonFragment";
	
	// Functionality
	private final String RATING;
	private final Context CONTEXT;

	// JSON tags
	private static final String TAG_ERROR = "error";
    private static final String TAG_MESSAGE = "message";
    private static final String TAG_GOOD = "good";
    private static final String TAG_BAD = "bad";

    private ServiceManager mServer;
    private String postJson = null;
    private String getJson = null;

    // total seconds of trying
    int total = 0;
    
	public RatingFragment (Context context) {
		Log.i(TAG, "new ComparisonFragment()");
		CONTEXT = context;
		RATING = CONTEXT.getResources().getString(R.string.rating);
	}
	
	@Override
	public void onActivityCreated (Bundle savedInstanceState) {
	    super.onCreate(savedInstanceState);

	    // this is where we refresh the data every 10 seconds, or w/e
	    
	    /* also by implementing onSaveInstanceState(Bundle outState),
	     * we could save the vote count from last time for more immediate display of
	     * something. 
	     */
	}
	
	@Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
            Bundle savedInstanceState) {
		View rootView = inflater.inflate(R.layout.fragment_rating, container, false);
        return rootView;
    }
	
	public static RatingFragment newInstance(Context context) {
		return new RatingFragment(context);
	}
	
	public void onClick(View button) {
		Log.i(TAG, "onClick()");
		
		int buttonId = button.getId();
		
		// Get my data
		List<NameValuePair> data = new ArrayList<NameValuePair>();
		String good = "0";
		String bad = "0";
		
		if (buttonId == R.id.button_high){
			good = "1";
		} else if (buttonId == R.id.button_low) {
			bad = "1";
		}
		
		// Add my data
		data.add(new BasicNameValuePair(CONTEXT.getResources().getString(R.string.good_key), 
				good));
		data.add(new BasicNameValuePair(CONTEXT.getResources().getString(R.string.bad_key), 
				bad));
		
	// run sequence to run HTTP requests, etc.
			mServer = new ServiceManager(CONTEXT, RATING);
			mServer.startService(data);
			
			getWithHandler();
		}
	
	/**
	 * Uses a handler to get JSON data
	 */
	public void getWithHandler() {
		// try every second for 5 seconds to get JSON strings 
		final Handler h = new Handler();
		final int DELAY = 1000;		//milliseconds
		final int MAX = 7000;		//milliseconds
		
		h.postDelayed(new Runnable(){
		    
			public void run(){
		    	
				if (ServiceManager.getStatus() == true) {
		    		postJson = mServer.getPOSTJson();
		    		getJson = mServer.getGETJson();
		    	}
		    	
		    	if (total <= MAX && postJson == null && getJson == null)
		    		h.postDelayed(this, DELAY);
		    	
		    	else if (postJson != null && getJson != null) {
		    		Log.i(TAG, "Seconds to retrieve data: " + total);
		    		
		    		// parse results
//		    		List<NameValuePair> postItems = new ArrayList<NameValuePair>();
//		    		postItems = parsePostJson(postJson);
		    		List<NameValuePair> getItems = new ArrayList<NameValuePair>();
		    		getItems = parseGetJson(getJson);
		    		
		    		// update the counter TextViews
		    		updateCounters(getItems);
		    		
		    	} else if (total > MAX) {	
		    		// data retrieval took too long
		    		Log.d(TAG, "Data retrieval took too long.");
		    		String toastText = "Something broke.";
		    		Toast.makeText(CONTEXT, toastText, Toast.LENGTH_SHORT).show();
		    	}
		    	
		    	// increment timer
		    	total += 1000;
		    }
		}, DELAY);
		
		Log.d(TAG, "after postDelayed stuff");
	}
	
	// Not actually used.
	/**
	 * Example method to extract single item from JSON.
	 * @param jsonStr
	 * @return List<NameValuePair> with Error Key and Error
	 */
	public List<NameValuePair> parseError (String jsonStr) {
		List<NameValuePair> results = new ArrayList<NameValuePair>();
		
		if (jsonStr != null) {

			// initialize error var
			boolean error = false;

			try {
				JSONObject jsonObj = new JSONObject(jsonStr);
				error = jsonObj.getBoolean(TAG_ERROR);
			} catch (JSONException e) {
				e.printStackTrace();
			}
			results.add(new BasicNameValuePair(
					CONTEXT.getResources().getString(R.string.error_key), 
					error + ""));
		} else {
			Log.e("ServiceManager", "Couldn't get any error data from the URL");
		}
		return results;
	}
	
	/**
     * Extracts meaningful data from JSON strings
     * @param jsonResponses contains POST then GET JSON responses
     */
	public List<NameValuePair> parsePostJson(String postJson) {
    	List<NameValuePair> postResults = new ArrayList<NameValuePair>();
    	String errorAlert = "Something went wroooong!";
    	
    	// parse POST JSON and take appropriate action
		if (postJson != null) {
			
			// initialize JSON values
			boolean postError = false;
			String postMessage =  null;

			try {
				JSONObject jsonObj = new JSONObject(postJson);
				postError = jsonObj.getBoolean(TAG_ERROR);
				postMessage = jsonObj.getString(TAG_MESSAGE);
			} catch (JSONException e) {
				e.printStackTrace();
				Log.e(TAG, "Error in parseGetJson()");
				Toast.makeText(CONTEXT, errorAlert, Toast.LENGTH_SHORT).show();
			}
			postResults.add(new BasicNameValuePair(
					CONTEXT.getResources().getString(R.string.post_error_key), 
					postError + ""));
			postResults.add(new BasicNameValuePair(
					CONTEXT.getResources().getString(R.string.post_message_key), 
					postMessage));
			
			if (postError) {	// post error
				String toastText = "Unable to record entry. Sorry about that.";
				Toast.makeText(CONTEXT, toastText, Toast.LENGTH_LONG).show();
			}
		} else {
			Log.e("ServiceManager", "Couldn't get any data from the URL");
			Toast.makeText(CONTEXT, errorAlert, Toast.LENGTH_SHORT).show();
		}
		return postResults;
	}
		
	public List<NameValuePair> parseGetJson (String getJson) {
		List<NameValuePair> getResults= new ArrayList<NameValuePair>();
		String errorAlert = "Something went wroooong!";
		
		// parse GET JSON and take appropriate action
		if (getJson != null) {
			
			// initialize vars
			boolean error	= false;
			String message	= null;
			String good 	= null;
            String bad 		= null;
			
			// get the JSONObject and store basic responses in ArrayList
			try {
				JSONObject jsonObj = new JSONObject(getJson);
            	error 	= jsonObj.getBoolean(TAG_ERROR);
            	message = jsonObj.getString(TAG_MESSAGE);
            	good 	= jsonObj.getString(TAG_GOOD);
				bad 	= jsonObj.getString(TAG_BAD);
			} catch (JSONException e) {
				e.printStackTrace();
				Log.e(TAG, "Error in parseGetJson()");
				Toast.makeText(CONTEXT, errorAlert, Toast.LENGTH_SHORT).show();
			}
			getResults.add(new BasicNameValuePair(
					CONTEXT.getResources().getString(R.string.get_error_key), 
					error + ""));
			getResults.add(new BasicNameValuePair(
					CONTEXT.getResources().getString(R.string.get_message_key), 
					message));
        	getResults.add(new BasicNameValuePair(
        			CONTEXT.getResources().getString(R.string.good_key), 
        			good));
        	getResults.add(new BasicNameValuePair(
        			CONTEXT.getResources().getString(R.string.bad_key), 
        			bad));
		}
		return getResults;
	}

    /**
     * Updates counters with parsed JSON data
     * @param result is a Key,Value list of data
     */
    public void updateCounters(List<NameValuePair> pairs) {
    	
    	String getErrorKey = CONTEXT.getResources().getString(R.string.get_error_key);
    	
    	// check for get error
    	if (getValueFromKey(pairs, getErrorKey).equals("false")) {
		    // get values
    		String good = getValueFromKey(pairs, 
    				CONTEXT.getResources().getString(R.string.good_key));
    		String bad = getValueFromKey(pairs, 
    				CONTEXT.getResources().getString(R.string.bad_key));
    		// set values
    		((TextView) (((Activity) CONTEXT).findViewById(R.id.good_counter))).setText(good);
    		((TextView) (((Activity) CONTEXT).findViewById(R.id.bad_counter))).setText(bad);
    	
    	// get error happened
    	} else {
    		String toastText = getValueFromKey(pairs, 
    				CONTEXT.getResources().getString(R.string.get_message_key)); 
    		Toast.makeText(CONTEXT, toastText, Toast.LENGTH_SHORT).show();
    	}
    }
    
    /**
     * Extracts value with name of parameter from List<NameValuePair>
     * @param list
     * @param key
     * @return value with key = 'key'
     */
    public String getValueFromKey(List<NameValuePair> list, String key) {
		
    	for (int i = 0; i < list.size(); i++) {
    		if (list.get(i).getName().equals(key))
    			return list.get(i).getValue();
    	}
    	// nothing found
    	return null;
    }
}