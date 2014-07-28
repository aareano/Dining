package valrae.tufts.dining;

import java.util.ArrayList;
import java.util.List;

import org.apache.http.NameValuePair;
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
 * @author Aaron Bowen
 */

// TODO create sharepreference IS_USER

// Flow:
// 
// User taps button >> HTTP Post venue vote >> HTTP Post venue tallies >> update display
// 						
//

public class RatingFragment extends Fragment implements ServiceListener {

	private final String TAG = "ComparisonFragment";
	
	// Functionality
	private final String[] PATHS;
	// Activity context
	private final Context CONTEXT;

	// JSON and Arraylist keys
	private final String ERROR_KEY;
    private final String MESSAGE_KEY;
    private final String PATH_KEY;
    private final String POS_KEY;
    private final String NEG_KEY;
    
    private ServiceManager mServer;
    
    // Preferences
    private static final String PREF_LAST_VENUE = "last_venue_selected";
    private static final String PREF_IS_DB_USER = "is_user_in_database";
    
    // Name of current venue
    private String mVenue;
    
    public static RatingFragment newInstance(Context context) {
    	return new RatingFragment(context);
    }

    public RatingFragment (Context context) {
		Log.i(TAG, "new RatingFragment()");
	
		CONTEXT = context;
		ERROR_KEY 	= CONTEXT.getResources().getString(R.string.error_key);
		MESSAGE_KEY = CONTEXT.getResources().getString(R.string.message_key);
		PATH_KEY 	= CONTEXT.getResources().getString(R.string.path_key);
		POS_KEY 	= CONTEXT.getResources().getString(R.string.negative_key);
		NEG_KEY 	= CONTEXT.getResources().getString(R.string.positive_key);
		PATHS 		= CONTEXT.getResources().getStringArray(R.array.url_array);
		
		mServer 	= new ServiceManager(CONTEXT);
	}
    
	@Override
	public void onCreate(Bundle savedInstanceState) {
		setHasOptionsMenu(true);
		updateVenue();

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
	    
	    String[] urls = CONTEXT.getResources().getStringArray(R.array.url_array);

	    List<NameValuePair> data = new ArrayList<NameValuePair>();
	    data.add(new BasicNameValuePair("name", mVenue));	// TODO make a resource
	    
	    if (mServer.isConnected()) {
	    	mServer.startService(data, urls[4]);		// more robust url get thing
	    	mServer.close();
	    }
	}
	
	
	
	public void onClick(View button) {
		Log.i(TAG, "onClick()");
		
		int buttonId = button.getId();
		
		// Get my data
		String pos = "0";
		String neg = "0";
		
		if (buttonId == R.id.button_high){
			pos = "1";
		} else if (buttonId == R.id.button_low) {
			neg = "1";
		}
		
		// Add my data
		List<NameValuePair> data = new ArrayList<NameValuePair>();
		data.add(new BasicNameValuePair(
				CONTEXT.getResources().getString(R.string.positive_key), pos));
		data.add(new BasicNameValuePair(
				CONTEXT.getResources().getString(R.string.negative_key), neg));
		
		
		// ensure is db user
		SharedPreferences sp = PreferenceManager.getDefaultSharedPreferences(CONTEXT);
		boolean isUser = sp.getBoolean(PREF_IS_DB_USER, false);
    	
    	if (!isUser) {
    		List<NameValuePair> userData = new ArrayList<NameValuePair>();
    		mServer.startService(userData, PATHS[0]);
    		mServer.close();
    	}
    	
    	//	TODO only allow vote if mVenue is not null
    	
    	// post vote, counters are updated automatically
    	if (mServer.isConnected()) {
			mServer.startService(data, PATHS[3]);
    		mServer.close();
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
				error = jsonObj.getBoolean(ERROR_KEY);
			} catch (JSONException e) {
				e.printStackTrace();
			}
			results.add(new BasicNameValuePair(ERROR_KEY, String.valueOf(error)));
		} else {
			Log.e("ServiceManager", "Couldn't get any error data from the URL");
		}
		return results;
	}
	
	/**
     * Extracts meaningful data from JSON strings
     * @param jsonResponses contains POST then GET JSON responses
     */
	public List<NameValuePair> parseJson(String json) {
    	List<NameValuePair> results = new ArrayList<NameValuePair>();
    	String errorAlert = "Something went wroooong!";
    	
    	// parse JSON and take appropriate action
		if (json != null) {
			
			// initialize JSON values
			boolean error 	= false;
			String message =  null;
			String path = null;
			int posVote = -1;
			int negVote = -1;

			try {
				JSONObject jsonObj = new JSONObject(json);
				error 	= jsonObj.getBoolean(ERROR_KEY);
				message = jsonObj.getString(MESSAGE_KEY);
				path 	= jsonObj.getString(PATH_KEY);
				
				for (int i = 0; i < PATHS.length; i++) {
					if (path.equals(PATHS[i])) {
						if (i == 4 || i == 5  || i == 8 || i == 9) {
							posVote = jsonObj.getInt(POS_KEY);
							negVote = jsonObj.getInt(NEG_KEY);
						}
					}
				}
			} catch (JSONException e) {
				e.printStackTrace();
				Log.e(TAG, "Error in parseJson()");
				Toast.makeText(CONTEXT, errorAlert, Toast.LENGTH_SHORT).show();
			}
			results.add(new BasicNameValuePair(ERROR_KEY, String.valueOf(error)));
			results.add(new BasicNameValuePair(PATH_KEY, path));
			results.add(new BasicNameValuePair(MESSAGE_KEY, message));
			if (posVote != -1 && negVote != -1) {
				results.add(new BasicNameValuePair(POS_KEY, String.valueOf(posVote)));
				results.add(new BasicNameValuePair(NEG_KEY, String.valueOf(negVote)));
			}
			
		} else {
			Log.e("ServiceManager", "Couldn't get any data from the URL");
			Toast.makeText(CONTEXT, errorAlert, Toast.LENGTH_SHORT).show();
		}
		
		return results;
	}
		
	
	/* ------------------------- UI methods ------------------------- */
	
    /**
     * Updates counters with parsed JSON data
     * @param result is a Key,Value list of data
     */
    public void updateCounters(List<NameValuePair> pairs) {
    	Log.i(TAG, "updateCounters()");
    	
    	// check for error
    	if (getValueFromKey(pairs, ERROR_KEY).equals("false")) {
		    
    		String posVotes = getValueFromKey(pairs, POS_KEY);
    		String negVotes = getValueFromKey(pairs, NEG_KEY);
    		
    		((TextView) (((Activity) CONTEXT).findViewById(R.id.positive_counter))).setText(posVotes);
    		((TextView) (((Activity) CONTEXT).findViewById(R.id.negative_counter))).setText(negVotes);
    	
    	// get error happened
    	} else {
    		String toastText = getValueFromKey(pairs, MESSAGE_KEY); 
    		Toast.makeText(CONTEXT, toastText, Toast.LENGTH_SHORT).show();
    	}
    }
    
    /**
     * Updates the MenuItem to reflect venue selection 
     */
    public void updateVenue() {						// TODO if null, prompt user to select venue
    	SharedPreferences sp = PreferenceManager.getDefaultSharedPreferences(CONTEXT);
    	mVenue = sp.getString(PREF_LAST_VENUE, null);
    	
    	if (mVenue != null) {
    		Log.d(TAG, "");	// TODO
    		((MenuItem) ((Activity) CONTEXT).findViewById(
    				R.id.action_venue)).setTitle(mVenue);
    	} else {
    		Log.d(TAG, "");	// TODO
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
                .getDefaultSharedPreferences(CONTEXT);
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

	
	@Override
	public void onServiceComplete(String json, String path) {	// TODO path is stored in json, redudant
		Log.i(TAG, "onServiceComplete()");
		mServer.close();
		
		List<NameValuePair> data = new ArrayList<NameValuePair>();
		data = parseJson(json);
		
		for (int i = 0; i < PATHS.length; i++) {
			if (PATHS[i].equals(path)) {
				switch (i) {
				case 0:		// create user
					if (getValueFromKey(data, ERROR_KEY).equals("false")) {
						SharedPreferences sp = PreferenceManager.getDefaultSharedPreferences(CONTEXT);
						sp.edit().putBoolean(PREF_IS_DB_USER, true).commit();
					} else {
						String text = "You appear to be on the naughty list and are\ntherefore not allowed to vote.";
						Toast.makeText(CONTEXT, text, Toast.LENGTH_LONG).show();
						Log.e(TAG + "::onServiceComplete()", data.toString());
					}
					break;
				case 3:		// venue vote
					if (getValueFromKey(data, ERROR_KEY).equals("false")) {
						String text = CONTEXT.getResources().getString(R.string.successful_vote);
						Toast.makeText(CONTEXT, text, Toast.LENGTH_LONG).show();
					
					} else {
						String recent = CONTEXT.getResources().getString(R.string.recent_message);
						
						if  (getValueFromKey(data, MESSAGE_KEY).equals(recent)) {		// too recent
							Log.e(TAG + "::onServiceComplete()", data.toString());
							String text = CONTEXT.getResources().getString(R.string.recency_error);
							Toast.makeText(CONTEXT, text, Toast.LENGTH_SHORT).show();
						
						} else if (getValueFromKey(data, ERROR_KEY).equals("true")) {	// actual error
							Log.e(TAG + "::onServiceComplete()", data.toString());
							String text = CONTEXT.getResources().getString(R.string.failed_vote);
							Toast.makeText(CONTEXT, text, Toast.LENGTH_LONG).show();
						}
					}
					break;
				case 4:		// venue tally
					if (getValueFromKey(data, ERROR_KEY).equals("false"))
						updateCounters(data);
					else {
						Log.e(TAG + "::onServiceComplete()", data.toString());
						String text = CONTEXT.getResources().getString(R.string.no_data);
						Toast.makeText(CONTEXT, text, Toast.LENGTH_LONG).show();
					}
					break;
					// TODO all other cases through 10
				default:	// error
					onError("An error happened in switch in onServiceComplete()");	// TODO uuugly
					break;
				}
			}
		}
		
		// if no error, update counters
		if (getValueFromKey(data, ERROR_KEY).equals("false"))
			updateCounters(data);
		else {
			String text = CONTEXT.getResources().getString(R.string.no_data);
			Toast.makeText(CONTEXT, text, Toast.LENGTH_LONG).show();
		}
	}

	@Override
	public void onCancel() {
		Log.d(TAG, "Process canceled");
		Toast.makeText(CONTEXT, "Process canceled", 
				Toast.LENGTH_SHORT).show();
	}

	@Override
	public void onError(String error) {
		Log.d(TAG, error);
		Toast.makeText(CONTEXT, "An error happened", 
				Toast.LENGTH_SHORT).show();
	}
}