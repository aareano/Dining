package valrae.tufts.dining;

import java.util.ArrayList;
import java.util.List;

import org.apache.http.NameValuePair;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.message.BasicNameValuePair;
import org.json.JSONException;
import org.json.JSONObject;

import android.app.Activity;
import android.content.Context;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.preference.PreferenceManager;
import android.support.v4.app.DialogFragment;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
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
	public static final String FUNCTIONALITY = "RATING";
	private final Context CONTEXT;

	// JSON tags
	private final String TAG_ERROR = "error";
    private final String TAG_MESSAGE = "message";
    private final String TAG_GOOD = "good";
    private final String TAG_BAD = "bad";

 // value keys
    private final String good_key;
    private final String bad_key;
    private final String get_error_key;
    private final String post_error_key;
    private final String post_message_key;
    private final String get_message_key;
    
    private ServiceManager mServer;
    
    // Preferences
    private static final String PREF_LAST_VENUE = "last_venue_selected";
    
    
    private String mVenue;
    
    
    public static RatingFragment newInstance(Context context) {
    	return new RatingFragment(context);
    }

    public RatingFragment (Context context) {
		Log.i(TAG, "new RatingFragment()");
	
		CONTEXT = context;
		get_error_key = CONTEXT.getResources().getString(R.string.get_error_key);
		post_error_key = CONTEXT.getResources().getString(R.string.post_error_key);
		post_message_key = CONTEXT.getResources().getString(R.string.post_message_key);
		get_message_key = CONTEXT.getResources().getString(R.string.get_message_key);
		good_key = CONTEXT.getResources().getString(R.string.good_key);
		bad_key = CONTEXT.getResources().getString(R.string.bad_key);
	}
    
	@Override
	public void onCreate(Bundle savedInstanceState) {
		setHasOptionsMenu(true);
		
		// read in the which venue was last selected
		SharedPreferences sp = PreferenceManager.getDefaultSharedPreferences(getActivity());
		mVenue = sp.getString(PREF_LAST_VENUE, null);
		
		super.onCreate(savedInstanceState);
	}
	
    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
    		Bundle savedInstanceState) {
    	View rootView = inflater.inflate(R.layout.fragment_rating, container, false);
    	updateVenue();
    	
    	return rootView;
    }

	@Override
	public void onCreateOptionsMenu(Menu menu, MenuInflater inflater) {
//		if (!mNavigationDrawerFragment.isDrawerOpen()) {	// TODO if drawer is open, let it decide
        inflater.inflate(R.menu.rating, menu);
		super.onCreateOptionsMenu(menu, inflater);
	}

	@Override
    public boolean onOptionsItemSelected(MenuItem item) {
        if (item.getItemId() == R.id.action_venue) {
        	Log.d(TAG, "show venue dialog");
        	
    		DialogFragment newFragment = new VenueDialogFragment();
    	    newFragment.show(getFragmentManager(), "VenueDialogFragment");
        }

        return super.onOptionsItemSelected(item);
    }
	
	@Override
	public void onActivityCreated (Bundle savedInstanceState) {
	    super.onCreate(savedInstanceState);

	    // TODO this is where we refresh the data every 10 seconds, or w/e
	    
	    /* also by implementing onSaveInstanceState(Bundle outState),
	     * we could save the vote count from last time for more immediate display of
	     * something. 
	     */
	    
	    mServer = new ServiceManager(CONTEXT, FUNCTIONALITY);
	    mServer.setRequestCompleteListener (new RequestCompletedListener() {
	    	
	    	@Override
			public void onComplete(String method, String json) {
				Log.i(TAG, "onComplete()");
				
				if (method.equals(HttpPost.METHOD_NAME)) {
					// do nothing
					
				} else if (method.equals(HttpGet.METHOD_NAME)) {
					mServer.close();
					
					List<NameValuePair> getData = new ArrayList<NameValuePair>();
					getData = parseGetJson(json);
					
					// if no error, update counters
					if (getValueFromKey(getData,get_error_key).equals("false"))
						updateCounters(getData);
					else {
						String text = CONTEXT.getResources().getString(R.string.no_data);
						Toast.makeText(CONTEXT, text, Toast.LENGTH_LONG).show();
					}
				}
			}

			@Override
			public void onCancel() {
				Log.d(TAG, "Process canceled");
				Toast.makeText(CONTEXT, "Process canceled", 
						Toast.LENGTH_SHORT).show();
			}
	    });
		if (mServer.isConnected()) {
			mServer.getTally();
			mServer.close();
		}
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
		mServer = new ServiceManager(CONTEXT, FUNCTIONALITY);
		mServer.setRequestCompleteListener (new RequestCompletedListener() {

			@Override
			public void onComplete(String method, String json) {
				Log.i(TAG, "onComplete()");
				String recent = CONTEXT.getResources().getString(R.string.recent_message);
				
				// POST
				if (method.equals(HttpPost.METHOD_NAME)) {
					List<NameValuePair> getData = new ArrayList<NameValuePair>();
					getData = parsePostJson(json);
					
					// too recent
					if (getValueFromKey(getData, post_message_key).equals(recent)) {
						String text = CONTEXT.getResources().getString(R.string.too_recent);
						Toast.makeText(CONTEXT, text, Toast.LENGTH_SHORT).show();
					
					// actual error
					} else if (getValueFromKey(getData, post_error_key).equals("true")) {
						String text = CONTEXT.getResources().getString(R.string.no_post);
						Toast.makeText(CONTEXT, text, Toast.LENGTH_SHORT).show();
					}
					mServer.getTally();
					
				// GET
				} else if (method.equals(HttpGet.METHOD_NAME)) {
					mServer.close();

					List<NameValuePair> getData = new ArrayList<NameValuePair>();
					getData = parseGetJson(json);
					updateCounters(getData);
				}
			}

			@Override
			public void onCancel() {
				String text = CONTEXT.getResources().getString(R.string.process_canceled);
				Toast.makeText(CONTEXT, text, Toast.LENGTH_SHORT).show();
			}
		});
		if (mServer.isConnected()) {
			mServer.postVote(data);
		}
	}
	
	/* ------------------------- JSON Parse methods ------------------------- */
	
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
			boolean postError 	= false;
			String postMessage =  null;

			try {
				JSONObject jsonObj = new JSONObject(postJson);
				postError 	= jsonObj.getBoolean(TAG_ERROR);
				postMessage = jsonObj.getString(TAG_MESSAGE);
			} catch (JSONException e) {
				e.printStackTrace();
				Log.e(TAG, "Error in parseGetJson()");
				Toast.makeText(CONTEXT, errorAlert, Toast.LENGTH_SHORT).show();
			}
			postResults.add(new BasicNameValuePair(
					post_error_key, postError + ""));
			postResults.add(new BasicNameValuePair(
					post_message_key, postMessage));
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
			getResults.add(new BasicNameValuePair(get_error_key, error + ""));
			getResults.add(new BasicNameValuePair(get_message_key, message));
        	getResults.add(new BasicNameValuePair(good_key, good));
        	getResults.add(new BasicNameValuePair(bad_key, bad));
		}
		return getResults;
	}

	/* ------------------------- UI methods ------------------------- */
	
    /**
     * Updates counters with parsed JSON data
     * @param result is a Key,Value list of data
     */
    public void updateCounters(List<NameValuePair> pairs) {
    	Log.i(TAG, "updateCounters()");
    	
    	// check for get error
    	if (getValueFromKey(pairs, get_error_key).equals("false")) {
		    
    		String good = getValueFromKey(pairs, good_key);
    		String bad 	= getValueFromKey(pairs, bad_key);
    		
    		((TextView) (((Activity) CONTEXT).findViewById(R.id.good_counter))).setText(good);
    		((TextView) (((Activity) CONTEXT).findViewById(R.id.bad_counter))).setText(bad);
    	
    	// get error happened
    	} else {
    		String toastText = getValueFromKey(pairs, get_message_key); 
    		Toast.makeText(CONTEXT, toastText, Toast.LENGTH_SHORT).show();
    	}
    }
    
    /**
     * Updates the MenuItem to reflect venue selection 
     */
    public void updateVenue() {						// TODO if null, prompt user to select venue
    	if (mVenue != null) {
    		((MenuItem) ((Activity) CONTEXT).findViewById(
    				R.id.action_venue)).setTitle(mVenue);
    		}
    	else {
    		String selectVenue = CONTEXT.getResources().getString(R.string.action_venue);
    		((MenuItem) ((Activity) CONTEXT).findViewById(
    				R.id.action_venue)).setTitle(selectVenue);
    	}
    }

    /* ------------------------- Dialog methods ------------------------- */
    
	public void onSelectionMade(int which) {
		String[] venues = CONTEXT.getResources().getStringArray(R.array.venues_array);
		mVenue = venues[which];
		
		SharedPreferences sp = PreferenceManager
                .getDefaultSharedPreferences(getActivity());
        sp.edit().putString(PREF_LAST_VENUE, null).commit();
        
        updateVenue();
	}
	
	public void onDialogNegativeClick() {
		
	}
	
	/* ------------------------- Other methods ------------------------- */
	
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