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
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;

/**
 * Fragment for comparison function: user votes for one or another.
 * Vote is stored at mysql.hellobiped.com
 * @author Aaron
 */
public class ComparisonFragment extends Fragment {

	private final String TAG = "ComparisonFragment";
	
	// Functionality
	private final String COMPARISON;
	private final Context CONTEXT;
	
	// JSON tags
	private static final String TAG_ERROR = "error";
    private static final String TAG_MESSAGE = "message";
    private static final String TAG_DEWICK = "dewick";
    private static final String TAG_CARM = "carm";

	public ComparisonFragment(Context context) {
		CONTEXT = context;
		COMPARISON = CONTEXT.getResources().getString(R.string.comparison);
		Log.i(TAG, "new ComparisonFragment()");
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
		View rootView = inflater.inflate(R.layout.fragment_comparison, container, false);
        return rootView;
    }
	
	public static ComparisonFragment newInstance(Context context) {
		return new ComparisonFragment(context);
	}
	
	public void onClick(View button) {
		Log.i(TAG, "onClick()");
		
		int buttonId = button.getId();
		
		// Get my data
		List<NameValuePair> data = new ArrayList<NameValuePair>();
		String dewick = "0";
		String carm = "0";
		
		if (buttonId == R.id.button_high){
			dewick = "1";
		} else if (buttonId == R.id.button_low) {
			carm = "1";
		}
		
		// Add my data
		data.add(new BasicNameValuePair(CONTEXT.getResources().getString(R.string.dewick_key), 
				dewick));
		data.add(new BasicNameValuePair(CONTEXT.getResources().getString(R.string.carm_key), 
				carm));
		
		// run sequence to get statistics
		ServiceManager server = new ServiceManager(CONTEXT, COMPARISON);
		server.startService(data);
		
		// get results
//		String postJson = null;				// unused
		String getJson = null;
		
		do {
//		postJson = server.getPOSTJson();
		getJson = server.getGETJson();
		
		try {						// wait a second
		    Thread.sleep(1000);
		} catch(InterruptedException ex) {
		    Thread.currentThread().interrupt();
		}
			
		} while (getJson == null);
		
		Log.d(TAG, "getJson = " + getJson);
		
		// parse results
//		List<NameValuePair> postItems = new ArrayList<NameValuePair>();
		List<NameValuePair> getItems = new ArrayList<NameValuePair>();
//		postItems = parsePostJson(postJson);
		getItems = parseGetJson(getJson);
		
		// update the counter TextViews
		updateCounters(getItems);
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
    	
    	// parse POST JSON and take appropriate action
		if (postJson != null) {
			
			// initialize JSON values
			boolean postError 	= false;
			String postMessage	= null;

			try {
				JSONObject jsonObj = new JSONObject(postJson);
				postError 	= jsonObj.getBoolean(TAG_ERROR);
				postMessage = jsonObj.getString(TAG_MESSAGE);
			} catch (JSONException e) {
				e.printStackTrace();
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
		}
		return postResults;
	}
		
	public List<NameValuePair> parseGetJson (String getJson) {
		List<NameValuePair> getResults= new ArrayList<NameValuePair>();
		
		// parse GET JSON and take appropriate action
		if (getJson != null) {
			
			// initialize vars
			boolean error 	= false;
			String message 	= null;
			String dewick 	= null;
            String carm 	= null;
			
			// get the JSONObject and store basic responses in ArrayList
			try {
				JSONObject jsonObj = new JSONObject(getJson);
            	error 	= jsonObj.getBoolean(TAG_ERROR);
            	message = jsonObj.getString(TAG_MESSAGE);
            	dewick 	= jsonObj.getString(TAG_DEWICK);
				carm 	= jsonObj.getString(TAG_CARM);
			} catch (JSONException e) {
				e.printStackTrace();
			}
			getResults.add(new BasicNameValuePair(
					CONTEXT.getResources().getString(R.string.get_error_key), 
					error + ""));
			getResults.add(new BasicNameValuePair(
					CONTEXT.getResources().getString(R.string.get_message_key), 
					message));
        	getResults.add(new BasicNameValuePair(
        			CONTEXT.getResources().getString(R.string.dewick_key), 
        			dewick));
        	getResults.add(new BasicNameValuePair(
        			CONTEXT.getResources().getString(R.string.carm_key), 
        			carm));
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
    		String dewick = getValueFromKey(pairs, 
    				CONTEXT.getResources().getString(R.string.dewick_key));
    		String carm = getValueFromKey(pairs, 
    				CONTEXT.getResources().getString(R.string.carm_key));
    		// set values
    		((TextView) (((Activity) CONTEXT).findViewById(R.id.dewick_counter))).setText(dewick);
    		((TextView) (((Activity) CONTEXT).findViewById(R.id.carm_counter))).setText(carm);
    	
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
    public String getValueFromKey(List<NameValuePair> result, String key) {
    	Log.d("ComparisonFragment", "key: " + key);
    	Log.d("ComparisonFragment", "arraylist result: " + result.toString());
    	
    	for (int i = 0; i < result.size(); i++) {
    		Log.d("ComparisonFragment", "result.get(i).getName() = " + result.get(i).getName());
    		if (result.get(i).getName().equals(key))
    			return result.get(i).getValue();
    	}
    	// nothing found
    	return null;
    }
}