/* Q: Sort the list of integers using heap tree (Heap sort) .*/
#include<iostream>
using namespace std;
void heapify(int arr[],int n,int i)
{
	int largest=i;
	int l=2*i+1;
	int r=2*i+2;
	if(l<n && arr[l]>arr[largest])
	 largest=l;
	if(r<n && arr[r]>arr[largest])
	 largest=r;
	if(largest!=i)
	{
		swap(arr[i],arr[largest]);
		heapify(arr,n,largest);
	}
}
void heap_sort(int arr[],int n)
{
	for(int i=(n/2)-1;i>=0;i--)
       heapify(arr,n,i);
    for(int i=n-1;i>=0;i--)
    {
    	swap(arr[0],arr[i]);
    	heapify(arr,i,0);
	}
}
void print(int arr[],int n)
{
	for(int i=0;i<n;i++)
	  cout<<arr[i]<<" ";
}
int main()
{
	int n;
	cout<<"enter the size : ";
	cin>>n;
	int arr[n];
	cout<<"enter elements"<<endl;
	for(int i=0;i<n;i++)
	  cin>>arr[i];
	heap_sort(arr,n);
	cout<<"Sorted array : ";
	print(arr,n);
	return 0;
}
